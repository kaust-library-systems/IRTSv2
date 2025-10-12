<?php
/*

**** Define function to prepare queries used for getting a list of repository records matching an individual
** Parameters :
	$controlName : Name of the individual (the only required parameter)
	$orcid : ORCID of the individual
	$types : Types of records to include in the list
	$workFields : Used when checking only for changes of specific fields
	$dateFields : Fields containing different types of dates related to the record
	$year : Year of publication
	$startYear : Start year of the range
	$endYear : End year of the range
	$from : Date from which to start checking for changes

** Output :
	$mysqlQuery : Query to get a list of repository record handles from MySQL
	$dspaceQuery : Query to search DSpace by API or for use in a link to the DSpace search page

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------
	function setIndividualPublicationListQueries($controlName, $orcid = NULL, $roles = ['author'], $types = NULL, $workFields = NULL, $dateFields = NULL, $year = NULL, $startYear = NULL, $endYear = NULL, $from = NULL)
	{
		global $irts;

		$mysqlQuery = "SELECT DISTINCT `idInSource`
			FROM `metadata`
			WHERE `source` LIKE 'repository'
			AND `deleted` IS NULL ";

		$dspaceQuery = "";

		$dspaceQueryParts = array();

		$dspaceFilters = array();

		$roleFields = array();
		$orcidRoleFields = [];

		foreach($roles as $role)
		{
			$roleFields[] = "dc.contributor.$role";
			$orcidRoleFields[] = "orcid.$role";
		}

		$rolesPlusName = [];

		foreach($roleFields as $roleField)
		{
			$rolesPlusName[] = $roleField . ":" . urlencode('"'.$controlName.'"');
		}

		$orcidRolesPlusORCID = [];

		foreach($orcidRoleFields as $roleField)
		{
			if(!empty($orcid))
			{
				$orcidRolesPlusORCID[] = $roleField . ":" . urlencode('"'.$orcid.'"');
			}
		}

		$mysqlQuery .= "AND `idInSource` IN(
			SELECT DISTINCT `idInSource`
			FROM `metadata`
			WHERE `source` LIKE 'repository'
			AND `field` IN ('".implode("','",$roleFields)."')
			AND `value` LIKE '$controlName'
			AND `deleted` IS NULL)
		";

		if(count($roles) == 1 && $roles[0] == 'author')
		{
			$dspaceFilters[] = 'f.author=' . urlencode($controlName) . ',equals';
		}
		else
		{
			$dspaceQueryParts[] = "(" . implode("%20OR%20",$rolesPlusName) . ")";
		}

		if(!empty($orcid))
		{
			$mysqlQuery .= "AND `idInSource` IN(
				SELECT DISTINCT orcid.idInSource
				FROM metadata author
				LEFT JOIN metadata orcid ON author.rowID=orcid.parentRowID
				WHERE author.`source` LIKE 'repository'
				AND author.field IN ('".implode("','",$roleFields)."')
				AND orcid.field LIKE 'dc.identifier.orcid'
				AND orcid.value LIKE '$orcid'
				AND orcid.deleted IS NULL)
			";

			//$dspaceQueryParts[] = "orcid.id:" . urlencode($orcid);

			$dspaceQueryParts[] = "(" . implode("%20OR%20",$orcidRolesPlusORCID) . ")";
		}

		if(!is_null($year))
		{
			if(is_numeric($year))
			{
				$mysqlQuery .= " AND `idInSource` IN(
				SELECT DISTINCT `idInSource`
				FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `field` IN ('".implode("','",$dateFields)."')
				AND `value` LIKE '$year%'
				AND `deleted` IS NULL)
				";

				if(count($dateFields) == 1 && $dateFields[0] == 'dc.date.issued')
				{
					$dspaceFilters[] = 'f.dateIssued.min=' . $year;

					$dspaceFilters[] = 'f.dateIssued.max=' . $year;
				}
				else
				{
					$dspaceQueryParts[] = "(" . implode("%20OR%20",array_map(function($dateField) use ($year) { return $dateField . ":" . $year; },$dateFields)) . ")";
				}
			}
			elseif($year === 'range')
			{					
				$mysqlQuery .= " AND `idInSource` IN(
					SELECT DISTINCT `idInSource`
					FROM `metadata`
					WHERE `source` LIKE 'repository'
					AND `field` IN ('".implode("','",$dateFields)."')
					AND `value` >= '$startYear'
					AND `value` < '$endYear'
					AND `deleted` IS NULL)
				";

				//for a range query, we will only use the dateIssued filter in DSpace
				$dspaceFilters[] = 'f.dateIssued.min=' . $startYear;

				$dspaceFilters[] = 'f.dateIssued.max=' . $endYear;
			}
		}

		if(!is_null($types))
		{
			$mysqlQuery .= " AND `idInSource` IN(
				SELECT DISTINCT `idInSource`
				FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `field` LIKE 'dc.type'
				AND `value` IN ('".implode("','",$types)."')
				AND `deleted` IS NULL)
			";

			if(count($types) == 1)
			{
				$dspaceFilters[] = 'f.itemtype=' . $types[0]  . ',equals';
			}
			else
			{
				$dspaceQueryParts[] = "(" . implode("%20OR%20",array_map(function($type) { return "dc.type:" . urlencode('"'.$type.'"'); },$types)) . ")";
			}
		}

		if(!is_null($from))
		{
			$mysqlQuery .= " AND `idInSource` IN(
				SELECT DISTINCT `idInSource`
				FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `field` IN ('".implode("','",$workFields)."')
				AND `added` >= '$from'
				AND `deleted` IS NULL)
			";

			//for a lastModified query in DSpace we can not define which fields were changed, the timestamp is for the last change of anything in the whole record
			$dspaceQueryParts[] = "lastModified%3A[" . urlencode($from) . "%20TO%20*]";
		}

		$dspaceQuery .= "query=" . implode("%20AND%20",$dspaceQueryParts);
		
		return array('mysqlQuery' => $mysqlQuery, 'dspaceQuery' => $dspaceQuery .'&'. implode("&",$dspaceFilters));
	}