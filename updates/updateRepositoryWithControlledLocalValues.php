<?php
	//Define function to update local persons, their ORCIDs, and departmental affiliations and related collection mappings
	function updateRepositoryWithControlledLocalValues($report, $errors, $recordTypeCounts)
	{
		global $irts;

		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();

		if($response['status'] == 'success')
		{	
			//Log in
			$response = dspaceLogin();
			
			if($response['status'] == 'success')
			{
				$unmatched = array();

				$failed = array();

				$recordTypeCounts['mapped'] = 0;

				$recordTypeCounts['skipped'] = 0;
				
				$recordTypeCounts['failed'] = 0;

				$queryParts = [];

				if(isset($_GET['customQuery'])) //custom query, such as "dc.type:Article", can not be used in combination with fromDate
				{
					$customQuery = urlencode($_GET['customQuery']);

					$report .= 'Custom Query: '.$customQuery.PHP_EOL;

					$queryParts[] = 'query='.$customQuery;

					$fromDate = '';
				}
				elseif(isset($_GET['department']))
				{
					//Use the filter from the browser
					//ex: Computer,%20Electrical%20and%20Mathematical%20Sciences%20and%20Engineering%20(CEMSE)%20Division,equals	
					$department = urlencode($_GET['department']);
					
					$report .= 'Department: '.$department.PHP_EOL;

					$queryParts[] = 'f.department='.$department;

					$fromDate = '';
				}
				elseif(!isset($_GET['fromDate'])) //not passing a fromDate parameter will use the fromDate set by the process the last time it ran
				{					
					$fromDate = getValues($irts, "SELECT `message` FROM messages WHERE process LIKE '$source' AND `type` LIKE 'nextHarvestFromDate' ORDER BY timestamp DESC LIMIT 1", array('message'), 'singleValue');
				}
				else //manually set a fromDate in the form "2025-06-26T00:00:00", passing an empty fromDate parameter will attempt to reprocess all records in the repository
				{
					$fromDate = $_GET['fromDate'];
				}
				
				if(!empty($fromDate))
				{
					//change to format needed in query
					$fromDateForQuery = explode('.', $fromDate)[0].'Z';
					
					if(!empty($_GET['toDate']))
					{
						$toDate = $_GET['toDate'];
		
						//base query - by lastModified range
						$queryParts[] = 'query=lastModified%3A['.$fromDateForQuery.'%20TO%20'.$toDate.']';
					}
					else
					{
						//base query - by lastModified, from date only
						$queryParts[] = 'query=lastModified%3A['.$fromDateForQuery.'%20TO%20*]';
					}
				}

				$baseQuery = implode('&', $queryParts);

				$page = 0;

				//continue paging until no further results are returned
				$continuePaging = TRUE;

				while($continuePaging)
				{
					if(!empty($page))
					{
						$query = $baseQuery.'&page='.$page;
					}
					else
					{
						$query = $baseQuery;
					}

					echo $query.PHP_EOL;
					
					$response = dspaceSearch($query);

					if($response['status'] == 'success')
					{
						$results = json_decode($response['body'], TRUE);
						
						if(!empty($response['body']))
						{
							$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

							echo $totalPages.PHP_EOL;
	
							foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
							{
								
								$itemReport = '';
								
								if(isset($result['_embedded']['indexableObject']))
								{
									$recordTypeCounts['all']++;
								
									$item = $result['_embedded']['indexableObject'];
									
									$handle = $item['handle'];
	
									$itemReport .= $recordTypeCounts['all'].") Handle: ".$handle.PHP_EOL;
									
									if(isset($item['metadata']['dc.type']))
									{
										$itemType = $item['metadata']['dc.type'][0]['value'];
	
										$itemReport .= '-- Type: '. $itemType.PHP_EOL;
										
										$template = prepareTemplate($itemType);
									
										if(isset($item['metadata']['dc.identifier.doi']) || isset($item['metadata']['kaust.identifier.irts']))
										{
											if(isset($item['metadata']['dc.identifier.doi']))
											{
												$doi = $item['metadata']['dc.identifier.doi'][0]['value'];
												
												$itemReport .= '-- DOI: '. $doi.PHP_EOL;
	
												$idInIRTS = getValues(
													$irts, 
													"SELECT `idInSource` FROM `metadata` 
														WHERE source = 'irts' 
														AND `field` = 'dc.identifier.doi' 
														AND `value` = '$doi' 
														AND `deleted` IS NULL", 
													array('idInSource'), 
													'singleValue');
											}
											else
											{
												$idInIRTS = $item['metadata']['kaust.identifier.irts'][0]['value'];
											}
	
											if(!empty($idInIRTS))
											{
												$itemReport .= '-- ID in IRTS: '. $idInIRTS.PHP_EOL;
	
												$metadata = prepareRecordForTransfer($template, $idInIRTS);
	
												if(!empty($metadata['dc.contributor.department']))
												{
													//prepare the new metadata with keys renumbered from 0 to match DSpace places
													$newMetadata = array('dc.contributor.department' => array_values($metadata['dc.contributor.department']));

													$itemReport .= 'Old Departments: '.print_r($item['metadata']['dc.contributor.department'], TRUE).PHP_EOL;
												
													$itemReport .= 'New Departments: '.print_r($newMetadata['dc.contributor.department'], TRUE).PHP_EOL;
													
													$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata,  __FUNCTION__, $item);
													
													$recordTypeCounts[$response['status']]++;
					
													$itemReport .= $response['report'].PHP_EOL;
								
													$itemReport .= '-- '.$response['status'].PHP_EOL;
													
													$errors = array_merge($errors, $response['errors']);
												}
												else
												{
													$itemReport .= '-- No departments found for this item.'.PHP_EOL;
													$itemReport .= '-- Skipping item.'.PHP_EOL;
													$recordTypeCounts['skipped']++;
												}
											}
											else
											{
												$itemReport .= '-- No ID in IRTS found for this item.'.PHP_EOL;
												$itemReport .= '-- Skipping item.'.PHP_EOL;
												$recordTypeCounts['skipped']++;
											}
										}
										else
										{
											$itemReport .= '-- No DOI found for this item.'.PHP_EOL;
											$itemReport .= '-- Skipping item.'.PHP_EOL;
											$recordTypeCounts['skipped']++;
										}
										
										echo $itemReport.PHP_EOL;
									}
								}
	
								ob_flush();
							}
							
							if(!isset($results['_embedded']['searchResult']['_links']['next']))
							{
								$continuePaging = FALSE;
							}
							else
							{
								$page++;
	
								if($page >= $totalPages)
								{
									$continuePaging = FALSE;
								}
							}
						}
						else
						{
							echo "Empty body recieved, trying the same page again".PHP_EOL;
						}
					}
					else
					{
						echo "Request failed: ".print_r($response, TRUE).PHP_EOL;
					}
					set_time_limit(0);

					sleep(2);
				}
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
?>
