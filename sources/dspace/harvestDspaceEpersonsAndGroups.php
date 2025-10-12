<?php
	//Define function to harvest repository metadata via DSpace REST API
	function harvestDspaceEpersonsAndGroups($source)
	{
		global $irts, $repository;

		$report = '';

		$errors = array();

		//base query - empty
		$queryParts = [];

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0);

		$recordTypeCounts['epersonsToGroups Rows Added'] = 0;

		$epersonFields = [
			'id',
			'name',
			'netid',
			'lastActive',
			'canLogIn',
			'email',
			'requireCertificate',
			'selfRegistered'
		];

		$groupFields = [
			'id',
			'name',
			'permanent'
		];

		if(isset($_GET['types'])) //types concatenated with comma, such as "epersons,groups"
		{
			$types = explode(',', $_GET['types']);

			foreach($types as $type)
			{
				$type = trim($type);

				if(!in_array($type, ['epersons', 'groups']))
				{
					$errors[] = 'Invalid type: '.$type.'. Only "epersons" and "groups" are allowed.';
					return array('changedCount'=>0, 'summary'=>saveReport($irts, __FUNCTION__, implode(PHP_EOL, $errors), $recordTypeCounts, $errors));
				}
			}
		}
		else
		{
			$types = ['epersons', 'groups'];
		}

		$report .= 'Types: '.implode(', ', $types).PHP_EOL;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
		
		if($response['status'] == 'success')
		{
			//Log in
			$response = dspaceLogin();

			if($response['status'] == 'success')
			{
				foreach($types as $type)
				{
					//delete all rows (always replace all data, regardless of mode)
					$result = $repository->query("TRUNCATE TABLE ".$type);
					//delete all rows in epersonsToGroups if type is groups
					if( $type=='groups')
					{
						$result = $repository->query("TRUNCATE TABLE epersonsToGroups");
					}
					$page = 0;

					$recordTypeCounts[$type.' Rows Added'] = 0;
					
					$report .= 'Harvesting '.$type.'...'.PHP_EOL;

					echo 'Harvesting '.$type.'...'.PHP_EOL;
					
					//continue paging until no further results are returned
					$continuePaging = TRUE;

					while($continuePaging)
					{
						$report .= 'Page: '.$page.PHP_EOL;

						echo 'Page: '.$page.PHP_EOL;
						
						$response = dspaceGetEpersonsOrGroups($type, $page);

						if($response['status'] == 'success')
						{
							$results = json_decode($response['body'], TRUE);

							$totalPages = $results['page']['totalPages'];

							//echo $totalPages.PHP_EOL;

							$totalElements = $results['page']['totalElements'];

							//echo 'Total elements: '.$totalElements.PHP_EOL;

							if(isset($results['_embedded']))
							{
								foreach($results['_embedded'][$type] as $entry)
								{
									$recordTypeCounts['all']++;
									
									//empty array to hold entry values
									$row = array();

									if($type === 'epersons')
									{
										//get eperson fields
										foreach($epersonFields as $field)
										{
											if(isset($entry[$field]))
											{
												if($entry[$field] === TRUE)
												{
													$row[$field] = 'TRUE'; //convert boolean to string
												}
												elseif($entry[$field] === FALSE)
												{
													$row[$field] = 'FALSE'; //convert boolean to string
												}
												else
												{
													$row[$field] = $entry[$field];
												}
											}
											else
											{
												$row[$field] = NULL;
											}
										}

										//get first and last name from metadata
										if(isset($entry['metadata']))
										{
											foreach($entry['metadata'] as $key => $metadata)
											{
												if($key === 'eperson.firstname')
												{
													$row['firstname'] = $metadata[0]['value'];
												}
												elseif($key === 'eperson.lastname')
												{
													$row['lastname'] = $metadata[0]['value'];
												}
											}
										}
									}
									elseif($type === 'groups')
									{
										//get group fields
										foreach($groupFields as $field)
										{
											if(isset($entry[$field]))
											{
												if($entry[$field] === TRUE)
												{
													$row[$field] = 'TRUE'; //convert boolean to string
												}
												elseif($entry[$field] === FALSE)
												{
													$row[$field] = 'FALSE'; //convert boolean to string
												}
												else
												{
													$row[$field] = $entry[$field];
												}
											}
											else
											{
												$row[$field] = 'NULL';
											}
										}

										//get epersons in this group
										$groupUUID = $entry['id'];

										$report = 'Getting epersons for group '.$groupUUID.'...'.PHP_EOL;
										echo 'Getting epersons for group '.$groupUUID.'...'.PHP_EOL;

										$epersonPage = 0;

										//continue paging until no further results are returned
										$continueEpersonPaging = TRUE;

										while($continueEpersonPaging)
										{
											$report .= '- Eperson Page: '.$epersonPage.PHP_EOL;

											echo '- Eperson Page: '.$epersonPage.PHP_EOL;

											$epersonsResponse = dspaceGetGroupEpersons($groupUUID, $epersonPage);

											if($epersonsResponse['status'] == 'success')
											{
												$epersonsResults = json_decode($epersonsResponse['body'], TRUE);

												$epersonTotalPages = $epersonsResults['page']['totalPages'];

												if(isset($epersonsResults['_embedded']['epersons']))
												{
													foreach($epersonsResults['_embedded']['epersons'] as $eperson)
													{
														$mappingRow = array(
															'eperson name' => $eperson['name'],
															'eperson id' => $eperson['id'],
															'group name' => $row['name'],
															'group id' => $row['id']
														);

														//insert row in table
														if(addRow('epersonsToGroups', $mappingRow))
														{
															$recordTypeCounts['epersonsToGroups Rows Added']++;

															echo $recordTypeCounts['epersonsToGroups Rows Added'].' epersons to groups rows added.'.PHP_EOL;
														}
													}
												}
											}
											else
											{
												$errors[] = 'Failed to get epersons for group '.$groupUUID.': '.$epersonsResponse['body'];
											}

											//increment page number
											$epersonPage++;

											if($epersonPage >= $epersonTotalPages)
											{
												$continueEpersonPaging = FALSE;
											}
										}
									}

									//insert row in table
									if(addRow($type, $row))
									{
										$recordTypeCounts[$type.' Rows Added']++;
									}
								}
							}
							
							//increment page number
							$page++;

							if($page >= $totalPages)
							{
								$continuePaging = FALSE;
							}
						}
						else
						{
							echo 'DSpace Get '.$type.' request failed.'.PHP_EOL;
							
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
