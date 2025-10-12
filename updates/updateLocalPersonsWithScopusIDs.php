<?php
	//Define function to add Scopus IDs as local person data based on name matches
	function updateLocalPersonsWithScopusIDs($report, $errors, $recordTypeCounts)
	{
		global $irts, $errors;

		$scopusIDsByName = array();

		$scopusIDsAndNames = getValues($irts, "SELECT DISTINCT authorid.value scopusid, authorname.value name FROM `metadata` authorid LEFT JOIN `metadata` authorname ON authorid.`parentRowID` = authorname.`rowID`
		WHERE authorid.`source` LIKE 'scopus'
		AND authorid.`parentRowID` IN (
		    SELECT `rowID` FROM `metadata`
		    WHERE `source` LIKE 'scopus'
			AND `rowID` IN (
		        SELECT `parentRowID` FROM `metadata`
		        WHERE `source` LIKE 'scopus'
		        AND `rowID` IN (
		            SELECT `parentRowID` FROM `metadata`
		            WHERE `source` LIKE 'scopus'
		            AND `field` LIKE 'dc.identifier.scopusid'
		            AND `value` LIKE '60092945'
		            AND `deleted` IS NULL
		        )
		        AND `field` LIKE 'dc.contributor.affiliation'
		        AND `deleted` IS NULL
		    )
		    AND `field` LIKE 'dc.contributor.author'
		    AND `deleted` IS NULL
		)
		AND authorid.`field` LIKE 'dc.identifier.scopusid'
		AND authorid.`deleted` IS NULL", array('scopusid','name'), 'arrayOfValues');
		
		foreach($scopusIDsAndNames as $scopusIDandName)
		{
			$scopusIDsByName[$scopusIDandName['name']][] = $scopusIDandName['scopusid'];
		}
		
		//print_r($scopusIDsAndNames);

		$itemCounts = array();

		$scopusIDsAndItemCounts = getValues($irts, "SELECT authorid.value scopusid, COUNT(*) AS `ItemCount` 
			FROM `metadata` authorid 
			LEFT JOIN `metadata` authorname ON authorid.`parentRowID` = authorname.`rowID`
			WHERE authorid.`source` LIKE 'scopus'
			AND authorid.`parentRowID` IN (
				SELECT `rowID` FROM `metadata`
				WHERE `source` LIKE 'scopus'
				AND `rowID` IN (
					SELECT `parentRowID` FROM `metadata`
					WHERE `source` LIKE 'scopus'
					AND `rowID` IN (
						SELECT `parentRowID` FROM `metadata`
						WHERE `source` LIKE 'scopus'
						AND `field` LIKE 'dc.identifier.scopusid'
						AND `value` LIKE '60092945'
						AND `deleted` IS NULL
					)
					AND `field` LIKE 'dc.contributor.affiliation'
					AND `deleted` IS NULL
				)
				AND `field` LIKE 'dc.contributor.author'
				AND `deleted` IS NULL
			)
			AND authorid.`field` LIKE 'dc.identifier.scopusid'
			AND authorid.`deleted` IS NULL
			GROUP BY `scopusid`
			ORDER BY ItemCount DESC", array('scopusid','ItemCount'), 'arrayOfValues');
		
		//print_r($scopusIDsAndItemCounts);

		foreach($scopusIDsAndItemCounts as $scopusIDAndItemCount)
		{
			$itemCounts[$scopusIDAndItemCount['scopusid']] = $scopusIDAndItemCount['ItemCount'];
		}

		$academics = getValues($irts, "SELECT id.idInSource, id.value kaustid, name.value name
			FROM metadata id 
			LEFT JOIN metadata name ON id.idInSource = name.idInSource
			WHERE id.source = 'local'
			AND id.field = 'local.person.id'
			AND (id.idInSource IN (
				SELECT idInSource FROM metadata
					WHERE source = 'local'
					AND field = 'local.staff.type'
					AND value LIKE 'Academic'
					AND deleted IS NULL
				)
			OR id.idInSource IN (
				SELECT idInSource FROM metadata
					WHERE source = 'local'
					AND field = 'local.employment.type'
					AND value LIKE 'Student'
					AND deleted IS NULL
				)
			)
			AND name.source = 'local'
			AND name.field = 'local.person.name'
			AND id.deleted IS NULL
			AND name.deleted IS NULL", array('idInSource', 'kaustid', 'name'), 'arrayOfValues');
			
		//print_r($academics);

		foreach($academics as $academic)
		{
			$recordTypeCounts['all']++;
			
			$report .= print_r($academic, TRUE);

			$idInSource = $academic['idInSource'];

			$nameVariants = getValues($irts, setSourceMetadataQuery('local', $idInSource, NULL, 'local.name.variant'), array('value'));

			$nameVariants[] = $academic['name'];

			$matchedScopusIDs = array();

			foreach($nameVariants as $nameVariant)
			{
				if(isset($scopusIDsByName[$nameVariant]))
				{
					foreach($scopusIDsByName[$nameVariant] as $scopusID)
					{
						$matches = array_unique(getValues($irts, setSourceMetadataQuery('local', NULL, NULL, array('local.person.name','local.name.variant'), $nameVariant), array('idInSource'), 'arrayOfValues'));

						//accept result and leave loop if unique match found
						if(count($matches) === 1)
						{
							$matchedScopusIDs[$scopusID] = $itemCounts[$scopusID];
						}
					}
				}
			}
			arsort($matchedScopusIDs);
			
			$report .= print_r($matchedScopusIDs, TRUE);

			if(!empty($matchedScopusIDs))
			{
				$result = saveValue('local', $idInSource, 'dc.identifier.scopusid', 1, array_keys($matchedScopusIDs)[0], NULL);

				$recordTypeCounts[$result['status']]++;
				
				//print_r($result);
				
				$report .= '- scopus id status: '.$result['status'].PHP_EOL;
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
