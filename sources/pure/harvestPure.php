<?php
	//Define function to harvest Pure person records
	function harvestPure($source)
	{
		global $irts, $errors, $report;

		$report = '';

		$errors = array();
		
		//$modifiedOnly = TRUE;
		$modifiedOnly = FALSE;

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);

		//endpoints to check
		$endpoints = ['organizations'];
		//$endpoints = ['organizations','persons','users'];
		//$endpoints = ['organizations','persons','research-outputs','users'];
		
		//$endpoints = ['applications','awards','events','external-organizations','external-persons','journals','organizations','persons','projects','publishers','research-outputs','roles','users'];
		
		//if set as parameter, use that instead
		if(isset($_GET['endpoint']) && in_array($_GET['endpoint'], $endpoints))
		{
			$endpoints = [$_GET['endpoint']];
		}
		
		foreach($endpoints as $endpoint)
		{
			$seenUUIDs = [];
			
			$report .= $endpoint.' harvest:'.PHP_EOL;
			
			$count = 0;
			
			$itemType = substr($endpoint, 0, -1);
			
			$response = queryPure($endpoint, 'GET', 0, 0);
		
			/* if(is_string($response))
			{
				$response = json_decode($response, TRUE);
				
				$total = $response['count'];
				
				$report .= '- total:'.$total.PHP_EOL;
			} */
		
			if($response['status'] == 'success')
			{
				$body = json_decode($response['body'], TRUE);
				
				$total = $body['count'];
				
				$report .= '- total:'.$total.PHP_EOL;

				//Run for all entries
				while($count < $total)
				{
					//Pure "modified" order sorts ascending only, so we have to start from the end of the results list and work backward
					if($modifiedOnly)
					{
						if($total > 1000)
						{
							$offset = $total - $count - 50;
						}
						else
						{
							$offset = $count;
						}
					}
					else
					{
						$offset = $count;
					}					
					
					$response = queryPure($endpoint, 'GET', $offset, 50);
					
					/* $response = json_decode($response, TRUE);
					
					$items = $response['items']; */
				
					$body = json_decode($response['body'], TRUE);
					
					$items = $body['items'];
					
					//so that most recently modified item is first
					if($modifiedOnly)
					{
						$items = array_reverse($items);
					}
					
					foreach($items as $item)
					{
						$recordTypeCounts['all']++;
						
						$count++;
						
						$uuid = $item['uuid'];
						
						$seenUUIDs[] = $uuid;
						
						/* if($modifiedOnly)
						{
							$modifiedDate = $item['modifiedDate'];
						
							$previousModifiedDate = getValues($irts, "SELECT value FROM `metadata`
								WHERE `source` LIKE 'pure'
								AND idInSource LIKE '$uuid'
								AND field = 'pure.$itemType.modifiedDate'
								AND `deleted` IS NULL", array('value'), 'singleValue');
								
							//exit loop when encountering first entry for which modified date has not changed
							if($modifiedDate == $previousModifiedDate)
							{
								$report .= ' - no more newly modified '.$endpoint.' - exiting '.$endpoint.' harvest.'.PHP_EOL;
								
								break 2;
							}
						} */
						
						$report .= ' - uuid: '.$uuid.PHP_EOL;
						
						$sourceData = json_encode($item);
						
						$result = saveSourceData($irts, $source, $uuid, $sourceData, 'JSON');

						$recordType = $result['recordType'];

						$report .= ' -- '.$source.' status: '.$recordType.PHP_EOL;

						$recordTypeCounts[$recordType]++;
						
						//only save values if source data is new or modified
						if($recordType !== 'unchanged')
						{
							$record = processPureRecord('pure.'.$itemType, $item);
						
							$record['pure.item.type'][0]['value'] = $itemType;
							
							//print_r($record);
							
							$result = saveValues($source, $uuid, $record, NULL);
							
							//$report .= ' -- save values result: '.$result.PHP_EOL;
						}
						flush();
						set_time_limit(0);
					}
				}
				
				if(!$modifiedOnly)
				{
					$existingUUIDs = getValues($irts, "SELECT idInSource FROM `metadata`
						WHERE `source` LIKE 'pure'
						AND field = 'pure.item.type'
						AND value LIKE '$itemType'
						AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
					
					$missingUUIDs = array_diff($existingUUIDs, $seenUUIDs);
					
					foreach($missingUUIDs as $missingUUID)
					{
						$query = "UPDATE `metadata` 
							SET `deleted` = '".date("Y-m-d H:i:s")."'
							WHERE `idInSource` = '$missingUUID'
							AND deleted IS NULL";
									
						$updated = $irts->query($query);

						//check for success of query
						if($updated)
						{
							$report .= ' - record metadata entries marked deleted'.PHP_EOL;
							
							$query = "UPDATE `sourceData` 
							SET `deleted` = '".date("Y-m-d H:i:s")."'
							WHERE `idInSource` = '$missingUUID' 
							AND deleted IS NULL";
						
							$updated = $irts->query($query);

							//check for success of query
							if($updated)
							{
								$report .= ' - duplicate record sourceData entries marked deleted'.PHP_EOL;
							}
							else
							{
								$report .= ' - Failed query: '.$query.PHP_EOL;
							}
						}
						else
						{
							$report .= ' - Failed query: '.$query.PHP_EOL;
						}
						
						$recordTypeCounts['deleted']++;
					}
				}
			}
			else
			{
				print_r($response);
			}
		}
		
		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
