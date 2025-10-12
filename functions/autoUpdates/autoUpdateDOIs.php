<?php	
	//Define function to remove duplicates and clean up values in repository record dc.identifier.doi fields
	function autoUpdateDOIs($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//get handles and DOIs of items that have more than one DOI
		$items = getValues(
			$irts, 
			"SELECT idInSource, value doi 
				FROM `metadata` 
				WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.identifier.doi' 
				AND `deleted` IS NULL 
				GROUP BY idInSource, doi HAVING COUNT(rowID) > 5
				ORDER BY COUNT(rowID) DESC", 
			array('idInSource','doi'), 
			'arrayOfValues'
		);

		//add count of items with duplicate DOIs to report
		$header = PHP_EOL.'Found '.count($items).' items with duplicate DOIs.'.PHP_EOL;
		echo $header;
		$report .= $header;
		
		//loop through records with duplicate DOIs
		foreach($items as $item)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$handle = $item['idInSource'];
			$doi = $item['doi'];
			
			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL.'-- '.$doi.PHP_EOL;
			
			//get existing metadata for the item
			$response = dspaceGetItemByHandle($handle);

			if($response['status'] == 'success')
			{				
				$item = json_decode($response['body'], TRUE);
				
				//metadata as simple array
				$record = dSpaceMetadataToArray($item['metadata']);

				//get existing DOIs
				$existingDOIs = $record['dc.identifier.doi'];

				//echo list of existing DOIs
				$itemReport .= '-- Existing DOIs: '.implode(', ', $existingDOIs).PHP_EOL;

				//remove duplicates and set as new metadata
				$newDOIs = array_unique($existingDOIs);

				//if there is no change, skip the item
				if(count($newDOIs) == count($existingDOIs))
				{
					$itemReport .= '-- No change for item: '.$handle.PHP_EOL;
					$recordTypeCounts['unchanged']++;
				}
				else
				{
					$itemReport .= '-- New DOIs: '.implode(', ', $newDOIs).PHP_EOL;

					$newMetadata = array('dc.identifier.doi' => $newDOIs);

					//prepare and apply patch to item
					$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, __FUNCTION__);

					$recordTypeCounts[$response['status']]++;

					$itemReport .= $response['report'];

					$itemReport .= '-- '.$response['status'].PHP_EOL;

					$errors = array_merge($errors, $response['errors']);
				}
			}
			else
			{
				$itemReport .= '-- Failed to get item by handle: '.$handle.PHP_EOL;
				$errors[] = 'Failed to get item by handle: '.$response;
				$recordTypeCounts['failed']++;
			}

			$report .= $itemReport;

			echo $itemReport;
		}

		//get handles and DOIs of items that have a DOI in the form of a link
		$items = getValues(
			$irts, 
			"SELECT idInSource, value doi 
				FROM `metadata` 
				WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.identifier.doi' 
				AND `value` LIKE 'https://doi.org/%'
				AND `deleted` IS NULL",
			array('idInSource','doi'), 
			'arrayOfValues'
		);

		//add count of items with DOIs in the form of a link to report
		$header = PHP_EOL.'Found '.count($items).' items with DOIs in the form of a link.'.PHP_EOL;
		echo $header;
		$report .= $header;

		//loop through records with DOIs in the form of a link
		foreach($items as $item)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$handle = $item['idInSource'];
			$doi = $item['doi'];
			
			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL.'-- '.$doi.PHP_EOL;

			//get existing metadata for the item
			$response = dspaceGetItemByHandle($handle);

			if($response['status'] == 'success')
			{				
				$item = json_decode($response['body'], TRUE);
				
				//metadata as simple array
				$record = dSpaceMetadataToArray($item['metadata']);

				//get existing DOIs
				$existingDOIs = $record['dc.identifier.doi'];

				//new DOIs array
				$newDOIs = array();

				foreach($existingDOIs as $existingDOI)
				{
					//remove link prefixes and add to new DOIs list
					$newDOIs[] = str_replace('https://doi.org/', '', $existingDOI);
				}

				$newMetadata = array('dc.identifier.doi' => $newDOIs);

				//prepare and apply patch to item
				$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, __FUNCTION__);

				$recordTypeCounts[$response['status']]++;

				$itemReport .= $response['report'];

				$itemReport .= '-- '.$response['status'].PHP_EOL;

				$errors = array_merge($errors, $response['errors']);
			}
			else
			{
				$itemReport .= '-- Failed to get item by handle: '.$handle.PHP_EOL;
				$errors[] = 'Failed to get item by handle: '.$response;
				$recordTypeCounts['failed']++;
			}

			$report .= $itemReport;

			echo $itemReport;
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
