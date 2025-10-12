<?php	
	//Define function to update repository record dc.publisher fields to match the publisher name on the Crossref record
	function updateRepositoryPublisherNamesBasedOnCrossref($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//Retrieve Crossref member names and save any changes
		//Currently avoid updating entry for Springer Nature as we have set this name manually for clarity
		$result = $irts->query("SELECT DISTINCT value, COUNT(`idInSource`) AS items FROM `metadata` WHERE `source` LIKE 'crossref' AND `field` LIKE 'crossref.member' AND value NOT IN ('297') GROUP BY value ORDER BY items DESC");

		while($row = $result->fetch_assoc())
		{			
			$idInSource = $row['value'];
			$report .= $idInSource.PHP_EOL;
			$sourceData = getCrossrefMemberByID($idInSource, $report);
			ob_flush();
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
