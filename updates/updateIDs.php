<?php
	//Define function to update IRTS IDs in the metadata table
	function updateIDs($report, $errors, $recordTypeCounts)
	{
		global $irts, $repository;

		//get IDs to be replaced
		$idsToReplace = getValues(
			$irts, 
			"SELECT DISTINCT(`idInSource`) FROM `metadata` 
				WHERE source = 'irts'
				AND (
                    idInSource LIKE 'url_%' 
					OR idInSource LIKE 'doi_%'
                    )
				AND field NOT LIKE 'irts.check%'
				AND `deleted` IS NULL", 
			array('idInSource'), 
			'arrayOfValues'
		);
		
		echo "Found ".count($idsToReplace)." IDs to replace.".PHP_EOL;

		//loop through the list
		foreach($idsToReplace as $idToReplace)
		{
			echo PHP_EOL."Replacing ID: $idToReplace".PHP_EOL;

			$recordTypeCounts['all']++;
			
			//generate new ID
			$idInIRTS = generateNewID('irts');

			echo "-- New ID: $idInIRTS".PHP_EOL;

			//update the metadata table with the new ID
			$irts->query("UPDATE `metadata` 
				SET `idInSource` = '$idInIRTS' 
				WHERE `source` = 'irts' 
				AND `idInSource` = '$idToReplace' 
				AND `deleted` IS NULL");

			//update the metadataReviewStatus table with the new ID
			$repository->query("UPDATE `metadataReviewStatus` 
				SET `ID in IRTS` = '$idInIRTS' 
				WHERE `ID in IRTS` = '$idToReplace'");

			//update the metadataSourceRecords table with the new ID
			$repository->query("UPDATE `metadataSourceRecords` 
				SET `ID in IRTS` = '$idInIRTS' 
				WHERE `ID in IRTS` = '$idToReplace'");

			ob_flush();
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
