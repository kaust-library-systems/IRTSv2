<?php
	//Define function to harvest EuropePMC metadata records
	function harvestEuropePMC($source, $harvestType)
	{
		global $irts, $newInProcess, $errors;

		$report = '';

		$errors = array();
		
		$records = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);
		
		if($harvestType === 'reharvest')
		{
			if(isset($_GET['doi']))
			{
				$results = queryEuropePMC('DOI', $_GET['doi']);

				$queryRecordsFound = $results['hitCount'];
				
				if($queryRecordsFound > 0)
				{
					foreach($results['resultList']['result'] as $result)
					{
						$idInSource = $result['id'];
						
						if(!in_array($idInSource, array_keys($records)))
						{
							$records[$idInSource]['harvestBasis'] = 'Added manually';
							
							$records[$idInSource]['recordType'] = processEuropePMCRecord($result);
						}
					}
				}
			}
		}
		else
		{
			$queryTypes = array('affiliation'=>'Harvested based on affiliation search','funding'=>'Harvested based on funding search');
		
			foreach($queryTypes as $queryType => $harvestBasis)
			{
				$queryRecordsRetrieved = 0;
				
				if($harvestType === 'requery')
				{
					$results = queryEuropePMC($queryType, NULL);

					$queryRecordsFound = $results['hitCount'];
				}			
				elseif($harvestType === 'new')
				{
					$queryRecordsFound = 1;
				}
				
				if($queryRecordsFound > 0)
				{
					//Start without cursor mark for first query
					$nextCursorMark = NULL;
					
					while($queryRecordsFound > $queryRecordsRetrieved)
					{
						$oldCursorMark = $nextCursorMark;
						
						$results = queryEuropePMC($queryType, NULL, $nextCursorMark);
						
						$nextCursorMark = $results['nextCursorMark'];
						
						if($nextCursorMark === $oldCursorMark)
						{
							//leave while statement if same cursor mark is received again, this means we are at the end of the results list
							break;
						}
						
						foreach($results['resultList']['result'] as $result)
						{
							$queryRecordsRetrieved++;
						
							$idInSource = $result['id'];
							
							//if record was added based on affiliation harvest, a duplicate based on acknowledgement harvest will not be added
							if(!in_array($idInSource, array_keys($records)))
							{
								//results that do not have author affiliation information will be skipped
								if(isset($result['authorList']['author'][0]['authorAffiliationDetailsList']))
								{
									$records[$idInSource]['harvestBasis'] = $harvestBasis;
									
									$records[$idInSource]['recordType'] = processEuropePMCRecord($result);
								}
								else
								{
									$recordTypeCounts['skipped']++;
									
									$report .= $idInSource.' - skipped due to lack of affiliation information'.PHP_EOL;
								}
							}
						}
					}
				}
			}
		}
		
		foreach($records as $idInSource => $entry)
		{
			$recordTypeCounts['all']++;
			
			$report .= $recordTypeCounts['all'].') '.$idInSource.PHP_EOL;
			
			$recordType = $entry['recordType'];
			
			$recordTypeCounts[$recordType]++;
			
			$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;
			
			$harvestBasis = $entry['harvestBasis'];
			
			if(strpos($idInSource, 'PMC') !== FALSE)
			{
				$idFieldInSource = 'dc.identifier.pmcid';
			}
			elseif(strpos($idInSource, 'PPR') !== FALSE)
			{
				$idFieldInSource = 'dc.identifier.other';
			}
			else
			{
				$idFieldInSource = 'dc.identifier.pmid';
			}
			
			//check for existing entries and add to IRTS as new entry if none found
			$result = addToProcess($source, $idInSource, $idFieldInSource, TRUE, $harvestBasis);

			if($result['status'] === 'inProcess')
			{
				$newInProcess++;
			}

			$report .= '- IRTS status: '.$result['status'].PHP_EOL;
		}
		
		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
