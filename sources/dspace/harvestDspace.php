<?php
	//Define function to harvest repository metadata via DSpace REST API
	function harvestDspace($source)
	{
		global $irts;

		$report = '';

		$errors = array();

		//used for cron task harvest only
		$saveFromDate = FALSE;

		//authentication is checked by presence of provenance field in item metadata
		$skipProvenanceCheck = FALSE;

		//base query - empty
		$queryParts = [];

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0);

		if(isset($_GET['customQuery'])) //custom query, such as "dc.type:Article", can not be used in combination with fromDate
		{
			$customQuery = urlencode($_GET['customQuery']);

			$report .= 'Custom Query: '.$customQuery.PHP_EOL;

			$queryParts[] = 'query='.$customQuery;

			$fromDate = '';
		}
		elseif(isset($_GET['scope'])) //use scope to harvest by collection or community UUID
		{
			$scope = $_GET['scope'];

			$report .= 'Scope: '.$scope.PHP_EOL;

			$queryParts[] = 'scope='.$scope;

			$fromDate = '';
		}
		elseif(!isset($_GET['fromDate'])) //pass empty fromDate parameter to run full reharvest
		{
			//if no customQuery or fromDate is set, this is assumed to be the cron task
			$saveFromDate = TRUE;

			//list of dates, results are not sorted by lastModified date so we have to sort again to get the last one
			$modifiedDates = [];
			
			$fromDate = getValues($irts, "SELECT `message` FROM messages WHERE process LIKE '$source' AND `type` LIKE 'nextHarvestFromDate' ORDER BY timestamp DESC LIMIT 1", array('message'), 'singleValue');
		}
		else
		{
			$fromDate = $_GET['fromDate'];
		}

		$report .= 'From Date: '.$fromDate.PHP_EOL;

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

		if(isset($_GET['dspaceObjectType']))
		{
			$dspaceObjectType = $_GET['dspaceObjectType'];

			$report .= 'DSpace Object Type: '.$dspaceObjectType.PHP_EOL;

			//limit harvest by object type
			$queryParts[] = 'dsoType='.$dspaceObjectType;

			//authentication check will be skipped for community and collection harvests
			if($dspaceObjectType !== 'item')
			{
				$skipProvenanceCheck = TRUE;
			}
		}

		if(isset($_GET['page']))
		{
			$page = $_GET['page'];
		}
		else
		{
			$page = 0;
		}
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
		
		if($response['status'] == 'success')
		{
			//Log in
			$response = dspaceLogin();

			if($response['status'] == 'success')
			{
				$baseQuery = implode('&', $queryParts);

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

						$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

						echo $totalPages.PHP_EOL;

						if($saveFromDate)
						{
							foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
							{
								if(isset($result['_embedded']['indexableObject']))
								{
									$item = $result['_embedded']['indexableObject'];

									if(isset($item['lastModified']))
									{
										//add lastModified timestamp to array
										$modifiedDates[] = $item['lastModified'];
									}
								}
							}
							
							rsort($modifiedDates);

							//if the last timestamp in the results is the same as the old timestamp, stop the harvest
							if(isset($modifiedDates[0]) && $fromDate == $modifiedDates[0])
							{
								break;
							}
						}
						
						if(!$skipProvenanceCheck)
						{
							//check if still authenticated based on presence of provenance field in item object metadata, reauthenticate if needed
							//this is recursive, it will check up to 5 times
							$result = dspaceCheckProvenanceAndReauthenticate($results, $query, 1);

							if($result['status'] == 'failed')
							{
								$error = '- dc.description.provenance not included in metadata - ending harvest due to missing provenance in records: '.print_r($result).PHP_EOL;

								$errors[] = $error;

								echo $error;
										
								$continuePaging = FALSE;

								//end processing, do not save records or new from date
								break;
							}
							else
							{
								$results = $result['results'];
							}
						}

						foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
						{
							if(isset($result['_embedded']['indexableObject']))
							{
								$recordTypeCounts['all']++;
							
								$object = $result['_embedded']['indexableObject'];
								
								$uuid = $object['uuid'];

								echo $uuid.PHP_EOL;
								
								$objectJSON = json_encode($object, JSON_PRETTY_PRINT);

								//save the sourceData in the database
								$result = saveSourceData($irts, 'dspace', $uuid, $objectJSON, 'JSON');

								$recordType = $result['recordType'];

								//echo $recordType.PHP_EOL;

								$recordTypeCounts[$recordType]++;
								
								//process the record
								$result = processDspaceRecord($objectJSON);

								$record = $result['record'];

								$report .= $result['report'];

								//echo $result['report'].PHP_EOL;
								
								//save it in the database
								$result = saveValues('dspace', $uuid, $record, NULL);

								//echo $result.PHP_EOL;

								$handle = $object['handle'];

								//if handle was for old version
								if(strpos($handle, '.') !== FALSE)
								{
									$handle = explode('.', $handle)[0];
								}

								if($object['type'] == 'item')
								{
									$withdrawn = $object['withdrawn'];
									$discoverable = $object['discoverable'];
	
									if($discoverable && !$withdrawn)
									{
										$existingFieldsToIgnore = [
											'dspace.date.modified',
											'dspace.community.handle',
											'dspace.collection.handle',
											'dspace.bundle.name',
											'dspace.record.visibility'
										];
										
										$result = saveValues('repository', $handle, $record, NULL, $existingFieldsToIgnore);
									}
									else
									{
										update($irts, 'sourceData', array('deleted'), array(date("Y-m-d H:i:s"), $handle), 'idInSource', ' AND deleted IS NULL');
	
										update($irts, 'metadata', array('deleted'), array(date("Y-m-d H:i:s"), $handle), 'idInSource', ' AND deleted IS NULL');
	
										$report .= " - metadata for $handle marked as deleted.".PHP_EOL;
	
										$recordTypeCounts['deleted']++;
									}
								}
								else //collections and communities cannot be withdrawn or made nondiscoverable
								{
									$result = saveValues('repository', $handle, $record, NULL);
								}
							}
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
						echo 'DSpace Search request failed.'.PHP_EOL;
						
						print_r($response);
						
						$errors[] = print_r($response, TRUE);

						//$continuePaging = FALSE;
					}
					set_time_limit(0);
					ob_flush();
					
					//sleep(5);

					//break after 1st query
					//$continuePaging = FALSE;
				}

				//print_r($modifiedDates);

				if($saveFromDate)
				{
					if(isset($modifiedDates[0]))
					{
						rsort($modifiedDates);

						//only save new from date if it has changed
						if($fromDate != $modifiedDates[0])
						{
							insert($irts, 'messages', array('process', 'type', 'message'), array('dspace', 'nextHarvestFromDate', $modifiedDates[0]));
						}
					}
				}
			}
			else
			{
				echo 'DSpace Login request failed.'.PHP_EOL;
				
				print_r($response);

				$errors[] = print_r($response, TRUE);
			}
		}
		else
		{
			echo 'DSpace Get Status request failed.'.PHP_EOL;
			
			print_r($response);

			$errors[] = print_r($response, TRUE);
		}
		
		$summary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
