<?php
	//Define function to merge duplicate records in the repository
	function autoUpdateMergeDuplicates($report, $errors, $recordTypeCounts) {
		global $irts;

		$recordTypeCounts['skipped'] = 0;
		$recordTypeCounts['markedForManualReview'] = 0;
		$recordTypeCounts['alreadyMarkedForManualReview'] = 0;
		$recordTypeCounts['merged'] = 0;

		$duplicateTypesToCheck = array(
			'arxiv' => array('idField' => 'dc.identifier.arxivid', 'types' => ['Preprint']),
			'doi' => array('idField' => 'dc.identifier.doi', 'types' => ['Article','Book Chapter','Conference Paper','Poster','Preprint','Presentation']),
			'bioproject' => array('idField' => 'dc.identifier.bioproject', 'types' => ['Bioproject']),
			'dataset' => array('idField' => 'dc.identifier.doi', 'types' => ['Data File','Dataset']),
			'software' => array('idField' => 'dc.identifier.github', 'types' => ['Software'])
		);
			
		//Check each type of duplicate
		foreach($duplicateTypesToCheck as $duplicateTypeToCheck)
		{
			$report .= PHP_EOL.'Checking for duplicates with '.$duplicateTypeToCheck['idField'].PHP_EOL;
			
			foreach($duplicateTypeToCheck['types'] as $type)
			{
				//item type and id must both match
				$report .= ' - '.$type.PHP_EOL;

				//Get all the IDs with multiple records
				$possibleDuplicates = getValues(
					$irts, 
					"SELECT value ID, COUNT(DISTINCT `idInSource`) duplicateCount FROM metadata 
						WHERE source = 'repository'
						AND `parentRowID` IS NULL
						AND field = '".$duplicateTypeToCheck['idField']."'
						AND deleted IS NULL
						AND idInSource IN (
							SELECT idInSource FROM metadata 
								WHERE source = 'repository'
								AND field = 'dc.type'
								AND value = '$type'
								AND deleted IS NULL
						)
						GROUP BY `value` HAVING duplicateCount > 1  
						ORDER BY duplicateCount DESC", 
					array('ID'),
					'arrayOfValues');

				foreach($possibleDuplicates as $possibleDuplicateID)
				{
					$recordTypeCounts['all']++;

					$itemReport = PHP_EOL.$recordTypeCounts['all'].') ID: '.$possibleDuplicateID.PHP_EOL;
					
					$recordWithFile = '';

					$recordsWithoutFiles = [];
					
					$skip = FALSE;
					
					//Get all the records with the ID
					$duplicateHandles = getValues(
						$irts, 
						"SELECT DISTINCT `idInSource` FROM `metadata` 
							WHERE `source` = 'repository' 
							AND `field` = '".$duplicateTypeToCheck['idField']."' 
							AND `value` = '$possibleDuplicateID'
							AND `deleted` IS NULL", 
						array('idInSource'),
						'arrayOfValues');

					foreach($duplicateHandles as $key => $duplicateHandle) {
						$itemReport .= ' - duplicate '.$key.': Handle: '.$duplicateHandle.PHP_EOL;
						
						//Get the UUID of the record
						$duplicateUUID = getValues(
							$irts, 
							setSourceMetadataQuery('repository', $duplicateHandle, NULL, 'dspace.uuid'), 
							array('value'), 
							'singleValue');

						$itemReport .= ' -- UUID: '.$duplicateUUID.PHP_EOL;

						//Check for existing files
						$hasFileInOriginalBundle = FALSE;

						$response = dspaceListItemBundles($duplicateUUID);

						if($response['status'] == 'success') {
							$bundles = json_decode($response['body'], TRUE);

							if(count($bundles['_embedded']['bundles'])===0)	{
								$itemReport .= '--- No Bundles'.PHP_EOL;
							}
							else {
								foreach($bundles['_embedded']['bundles'] as $bundle) {
									if($bundle['name'] == 'ORIGINAL') {
										$bundleUUID = $bundle['uuid'];

										$response = dspaceListBundlesBitstreams($bundleUUID);

										if($response['status'] == 'success')
										{
											$bitstreams = json_decode($response['body'], TRUE);

											$itemReport .= '--- Existing Files in ORIGINAL Bundle: '.count($bundles['_embedded']['bundles']).PHP_EOL;

											if(count($bundles['_embedded']['bundles']) > 0)	{
												$hasFileInOriginalBundle = TRUE;
											}
										}
									}
								}
							}
						}

						if($hasFileInOriginalBundle)
						{
							if(empty($recordWithFile))
							{
								$recordWithFile = ['handle' => $duplicateHandle, 'uuid' => $duplicateUUID];
							}
							else
							{
								$skip = TRUE;
							}
						}
						else
						{
							$recordsWithoutFiles[] = ['handle' => $duplicateHandle, 'uuid' => $duplicateUUID];
						}
					}

					if($skip)
					{
						$recordTypeCounts['skipped']++;

						$itemReport .= ' - skipped'.PHP_EOL;

						//limit to first two handles
						$duplicateHandles = array_slice($duplicateHandles, 0, 2);

						$possibleDuplicatePair = implode(':', $duplicateHandles);

						$itemReport .= '- Possible duplicate pair to mark for manual review: '.$possibleDuplicatePair.PHP_EOL;

						//check if already marked as possible duplicate pair 
						$possibleDuplicatePairStatus = getValues(
							$irts, 
							"SELECT value FROM `metadata` 
								WHERE `source` LIKE 'irts' 
								AND `idInSource` LIKE '$possibleDuplicatePair'
								AND `field` LIKE 'irts.duplicate.status' 
								AND `deleted` IS NULL", 
							array('value'), 
							'singleValue'
						);

						if(empty($possibleDuplicatePairStatus))
						{
							//mark as possible duplicate pair by saving status
							$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.status', 0, 'Possible Duplicates to Check', NULL);

							//save duplicate type description
							$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.type', 0, 'Two records with the same ID (DOI, arXiv ID, etc.)', NULL);

							$itemReport .= '- Marked as possible duplicate pair: '.$possibleDuplicatePair.PHP_EOL;

							$recordTypeCounts['markedForManualReview']++;
						}
						else
						{
							$itemReport .= '- Already marked as possible duplicate pair with status: '.$possibleDuplicatePairStatus.PHP_EOL;

							$recordTypeCounts['alreadyMarkedForManualReview']++;
						}
					}
					else
					{
						if(!empty($recordWithFile))
						{
							$mainRecord = $recordWithFile;
						}
						else
						{
							$mainRecord = $recordsWithoutFiles[0];

							unset($recordsWithoutFiles[0]);
						}

						$mainRecordHandle = $mainRecord['handle'];

						$mainRecordUUID = $mainRecord['uuid'];

						$itemReport .= ' - main record: '.$mainRecordHandle.PHP_EOL;
						
						foreach($recordsWithoutFiles as $key => $recordWithoutFile)
						{
							$duplicateHandle = $recordWithoutFile['handle'];

							$itemReport .= ' - duplicate '.$key.': '.$duplicateHandle.PHP_EOL;
							
							$duplicateUUID = $recordWithoutFile['uuid'];

							$itemReport .= ' -- UUID: '.$duplicateUUID.PHP_EOL;

							$provenanceStatement = 'Automatically merged with '.$mainRecordHandle.' at '.date('Y-m-d H:i:s').' by the autoMergeDuplicates task using the '.IR_EMAIL.' user account.';

							$result = mergeDuplicates($mainRecordHandle, $duplicateHandle, $mainRecordUUID, $duplicateUUID, $provenanceStatement);
							
							$status = $result['status'];

							if($status == 'success')
							{
								$recordTypeCounts['merged']++;

								$itemReport .= ' -- merged'.PHP_EOL;
							}
							else
							{
								$message = $result['message'];

								$itemReport .= ' -- error merging records: '.$message.PHP_EOL;

								$errors[] = 'Error merging records: '.$message;
							}
						}
					}

					$report .= $itemReport;
				}
			}
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
