<?php
	//Define function to query the Lens API
	function queryLens($harvestType)
	{
		global $irts, $newInProcess, $errors;
		
		$source = 'lens';
		
		$report = '';

		$report .= "Starting harvest of Lens.org $harvestType records".PHP_EOL;
		
		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);
		
		//for patent records, we query the predefined KAUST patent collection
		if($harvestType == 'patents')
		{
			$retrievedPatentRecordsCount = 0;

			$checkCollectionResult = file_get_contents(LENS_API_URL.'collections/'.LENS_KAUST_PATENT_COLLECTION_ID.'?size=0&token='.LENS_API_KEY);

			$checkCollectionData = json_decode($checkCollectionResult, true);
			$totalRecordsInCollection = $checkCollectionData['total'];

			while($retrievedPatentRecordsCount < $totalRecordsInCollection)
			{
				$url = LENS_API_URL.'collections/'.LENS_KAUST_PATENT_COLLECTION_ID.'?size=100&sort=desc(date_published)&from='.$retrievedPatentRecordsCount.'&token='.LENS_API_KEY;

				$batchResult = file_get_contents($url);

				$batchData = json_decode($batchResult);

				//check if data is present
				if(isset($batchData->data) && count($batchData->data) > 0)
				{
					$retrievedPatentRecordsCount += count($batchData->data);

					echo "Retrieved $retrievedPatentRecordsCount of $totalRecordsInCollection records so far...".PHP_EOL;
					
					foreach($batchData->data as $sourceData)
					{
						$recordTypeCounts['all']++;
			
						$recordReport = '';
						
						//get the lens.org ID
						$idInSource = $sourceData->lens_id;

						$recordReport .= "Processing $harvestType record with Lens ID $idInSource".PHP_EOL;
						
						//save source data as JSON
						$sourceDataAsJSON = json_encode($sourceData);
						$result = saveSourceData($irts, $source, $idInSource, $sourceDataAsJSON, 'JSON');

						//get record type from save result
						$recordType = $result['recordType'];

						//increment record type count
						$recordTypeCounts[$recordType]++;

						//append to record report
						$recordReport .= '- Record type: '.$recordType.PHP_EOL; 

						//decode json to array for processing
						$record = json_decode($sourceDataAsJSON, true);

						//process using harvest type specific function
						$record = processLensPatentRecord($record);
						
						//print_r($record);

						//save mapped and transformed record using saveValues function
						$saveValuesReport = saveValues($source, $idInSource, $record, NULL);

						//$recordReport .= $saveValuesReport;

						echo $recordReport;

						$report .= $recordReport;

						//break; //*** TEMPORARY - REMOVE LATER ***

						flush();
						set_time_limit(0);
					}
				}
				else
				{
					//no more records
					break;
				}

				//break; //*** TEMPORARY - REMOVE LATER ***
			}
		}

		$summary = saveReport($irts, $source.'_'.$harvestType, $report, $recordTypeCounts, $errors);

		//return summary and record type counts
		return array('summary'=>$summary, 'recordTypeCounts'=>$recordTypeCounts);
	}
