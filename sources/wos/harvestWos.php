<?php
	//The Web of Science Starter API documentation is at: https://developer.clarivate.com/apis/wos-starter	
	
	//Define function to harvest WOS metadata records
	function harvestWos($source, $harvestType)
	{
		global $irts, $newInProcess, $errors, $report;
		
		$report = '';

		$errors = array();
		
		$records = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);
		
		//print_r($recordTypeCounts).PHP_EOL;

		$queryTypes = array('affiliation'=>'Harvested based on affiliation search');
		
		foreach($queryTypes as $queryType => $harvestBasis)
		{
			$page = 1;
			
			$queryRecordsRetrieved = 0;
			
			if($harvestType === 'requery')
			{
				//Retrieve only first record to get the total results count
				$results = queryWos($queryType, NULL, $page, 1);
				
				$results = json_decode($results, TRUE);
				
				$queryRecordsFound = $results['metadata']['total'];
				
				//$queryRecordsFound = 1;
			}
			elseif($harvestType === 'new')
			{
				$queryRecordsFound = 1;
			}
			
			if($queryRecordsFound > 0)
			{
				while($queryRecordsFound > $queryRecordsRetrieved)
				{
					//max results per query is 50
					$pageSize = 50;
					//$pageSize = 1;
					
					$results = queryWos($queryType, NULL, $page, $pageSize);
					
					//print_r($results).PHP_EOL;
					
					$results = json_decode($results, TRUE);
					
					//print_r($results).PHP_EOL;
					
					if(isset($results['hits']))
					{
						foreach($results['hits'] as $record)
						{
							$queryRecordsRetrieved++;
							
							//print_r($record).PHP_EOL;
						
							$wosut = $record['uid'];
							
							if(!in_array($wosut, array_keys($records)))
							{
								$records[$wosut]['harvestBasis'] = $harvestBasis;
								
								$result = saveSourceData($irts, $source, $wosut, json_encode($record), 'JSON');
								$recordType = $result['recordType'];
								
								$records[$wosut]['recordType'] = $recordType;

								$record = processWosRecord($record);

								$functionReport = saveValues($source, $wosut, $record, NULL);
							}
						}
					}
					else
					{
						//leave while statement if unexpected response received from WOS API
						break;
					}
					
					//pause for 20 milliseconds to avoid rate limiting (5 requests per second allowed)
					usleep(20000);
					flush();
					set_time_limit(0);

					$page++;
				}
			}
		}
		
		foreach($records as $wosut => $entry)
		{
			$recordTypeCounts['all']++;
			
			$report .= $recordTypeCounts['all'].') '.$wosut.PHP_EOL;
			
			$recordType = $entry['recordType'];
			
			$recordTypeCounts[$recordType]++;
			
			$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;
			
			$harvestBasis = $entry['harvestBasis'];
			
			//check for existing entries and add to IRTS as new entry if none found
			$result = addToProcess('wos', $wosut, 'dc.identifier.wosut', TRUE, $harvestBasis);

			if($result['status'] === 'inProcess')
			{
				$newInProcess++;
			}

			$report .= '- IRTS status: '.$result['status'].PHP_EOL;
		}

		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
?>