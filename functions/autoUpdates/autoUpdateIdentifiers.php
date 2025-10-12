<?php	
	//Define function to add messing identifiers
	function autoUpdateIdentifiers($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//get handles of items that have no DOI, and no ID in IRTS
		$items = getValues(
			$irts, 
			"SELECT `idInSource` FROM `metadata` 
				WHERE `field` LIKE 'dspace.community.handle' 
				AND `value` LIKE '10754/324602' 
				AND `deleted` IS NULL 
				AND idInSource NOT IN (
					SELECT idInSource FROM `metadata` 
					WHERE `field` LIKE 'dc.identifier.doi' 
					AND `source` = 'repository' 
					AND `deleted` IS NULL)
				AND idInSource NOT IN (
					SELECT idInSource FROM `metadata` 
					WHERE `field` LIKE 'kaust.identifier.irts' 
					AND `source` = 'repository' 
					AND `deleted` IS NULL);", 
			array('idInSource'), 
			'arrayOfValues'
		);

		//add count of items with missing identifier to report
		$header = PHP_EOL.'Found '.count($items).' items with missing identifier.'.PHP_EOL;
		echo $header;
		$report .= $header;
		
		//loop through records with missing identifier
		foreach($items as $item)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$handle = $item;
			
			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL;

			echo $recordTypeCounts['all'].') '.$handle.PHP_EOL;

			//get title from database
			$title = getValues(
				$irts, 
				"SELECT `value` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `idInSource` = '$handle' 
					AND `field` LIKE 'dc.title' 
					AND `deleted` IS NULL;", 
				array('value'), 
				'singleValue'
			);

			//get type from database
			$type = getValues(
				$irts, 
				"SELECT `value` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `idInSource` = '$handle' 
					AND `field` LIKE 'dc.type' 
					AND `deleted` IS NULL;", 
				array('value'), 
				'singleValue'
			);
			
			if(!empty($title) && !empty($type))
			{
				$itemReport .= PHP_EOL.'-- Title: '.$title.PHP_EOL.'Type: '.$type.PHP_EOL;
				
				//prepare title for use in query
				$title = mysqli_real_escape_string($irts, $title);
					
				$idInIrts = getValues(
					$irts, 
					"SELECT idInSource FROM `metadata` 
						WHERE `source` LIKE 'irts' 
						AND `field` LIKE 'dc.title' 
						AND `value` LIKE '".$title."' 
						AND `deleted` IS NULL 
						AND idInSource IN (
							SELECT idInSource FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `field` LIKE 'dc.type' 
							AND `value` = '".$type."' 
							AND `deleted` IS NULL);", 
					array('idInSource'), 
					'singleValue'
				);

				if(!empty($idInIrts))
				{
					$itemReport .= PHP_EOL.'ID in IRTS: '.$idInIrts.PHP_EOL;
					
					$newMetadata = array('kaust.identifier.irts' => array(0 => $idInIrts));

					//prepare and apply patch to item
					$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, __FUNCTION__);

					$recordTypeCounts[$response['status']]++;

					$itemReport .= $response['report'];

					$itemReport .= '-- '.$response['status'].PHP_EOL;

					$errors = array_merge($errors, $response['errors']);
				}
				else
				{
					$itemReport .= PHP_EOL.'-- No ID in IRTS found for item. Skipping...'.PHP_EOL;
					
					$recordTypeCounts['skipped']++;
				}
			}
			else
			{
				$itemReport .= PHP_EOL.'-- No type set for item. Skipping...'.PHP_EOL;
				$recordTypeCounts['skipped']++;
			}

			$report .= $itemReport;

			//echo $itemReport;
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
