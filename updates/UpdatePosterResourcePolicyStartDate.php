5<?php
	//Define function to update the access policy for repository records
	function UpdatePosterResourcePolicyStartDate($report, $errors, $recordTypeCounts)
	{
		global $irts;
	

		if(!empty($_GET['collectionUUID']))
		{
			$collectionUUID = $_GET['collectionUUID'];

			//Get initial CSRF token and set in session
			$response = dspaceGetStatus();
					
			//Log in
			$response = dspaceLogin();
			if($response['status'] == 'success')
			{
				$baseQuery = 'scope='.$collectionUUID; // set scope based on collection UUID
			
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
					$Search = dspaceSearch($query);
					//print_r($Search);
					if($Search['status'] == 'success')
					{
						$results = json_decode($Search['body'], TRUE);
						$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];
										
						foreach($results['_embedded']['searchResult']['_embedded']['objects']as $result)
						{ 
							$item = $result['_embedded']['indexableObject'];
							$uuid = $item['uuid'];
							echo $uuid.PHP_EOL;
							$metadata =$item['metadata'];
							$record = dSpaceMetadataToArray($metadata);
							//$embargoEndDate= date('Y-m-d', strtotime('+12 months', strtotime($record['dc.date.issued'][0])));
							$embargoEndDate= '2024-07-24';
							//print_r($record);
							
								
							foreach($record['dc.description.provenance']as $value)
							{ 
								if(strpos($value, "Availability Options") !== FALSE)
								{  
							                           
							        //if(strpos($value, "Availability Options: Hold") !== FALSE)
										                 
							        if(strpos($value, "Availability Options :Hold") !== FALSE)
									{
										
										$record['dc.rights.embargodate'] =$embargoEndDate;
										$metadata = dspacePrepareItem($record, $uuid);
										$response = dspaceUpdateItem($uuid,$metadata);
										if($response['status'] == 'success')
										{
											echo 'embargo end date added to metadata';
										}
										else
										{
											print_r($response.PHP_EOL);
										}
									
										$flage= 'availabilitySelectionEmbargo';
										echo 'embargo for '.$uuid.PHP_EOL;
										//update embargo for item Policy 
										$ItemPolicy = dspaceResourcePolicies($uuid);
										$ItemPolicy = json_decode($ItemPolicy['body'], TRUE);
										if(isset(($ItemPolicy['_embedded']['resourcepolicies'])))
										{
											foreach (($ItemPolicy['_embedded']['resourcepolicies'])as $ItemPolicy)
											{
												$ItemPolicyID = $ItemPolicy['id']; 
												//change start date to the policy 
												 
												$embargoDateArray= [];
													
												$embargoDateArray[] =  array("op" => "replace",
												"path"=> "/startDate","value" => $embargoEndDate);
												$embargoDate = json_encode($embargoDateArray);
												$response = dspaceUpdateResourcePolicy($ItemPolicyID,$embargoDate);
												if($response['status'] == 'success')
												{
													echo '-- embargo end date updated for '.$uuid.PHP_EOL;
																	
												}
												if($response['status'] == 'failed')
												{
													echo '-- embargo end date failed to be updated for  '.$uuid.PHP_EOL;
													print_r($response);
												}
									             
											}
										}
										else
										{
											//add resource policy 
											
											$bodyArray =  array("startDate" => $embargoEndDate,
											"action" => "READ");
											$body = json_encode($bodyArray);
											//print_r($body);
										    $response =dspaceCreateResourcePolicy($uuid, 'group', ANONYMOUS_GROUP_ID, $body);
											//print_r($response);
											if($response['status'] == 'success')
											{
												echo '-- Policy added for '.$uuid.PHP_EOL;
																	
											}
											if($response['status'] == 'failed')
											{
												echo '-- Policy failed to be added for  item '.$uuid.PHP_EOL;
												print_r($response);
											}
												
										}
										
										//update embargo for bitstream
										$ItemBundles = dspaceListItemBundles($uuid);	
										$ItemBundles = json_decode($ItemBundles['body'], TRUE);
										foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
										{
											$ItemBundlesName = $ItemBundle['name'];
											$ItemBundleID = $ItemBundle['uuid'];
											if ($ItemBundlesName == 'ORIGINAL')
											{
												$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
												$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);
												foreach(($bundleBitstreams['_embedded']['bitstreams'])as $Bitstream)
												{
													$BitstreamName = $Bitstream['name'];
													$BitstreamID = $Bitstream['id'];
													$BitsreamPolicy = dspaceResourcePolicies($BitstreamID);
													$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);
													if(isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
													{
														foreach (($BitsreamPolicy['_embedded']['resourcepolicies'])as $Policy)
														{
															$BitsreamPolicyID= $Policy['id'];
															$UpdatePolicy = dspaceUpdateGroupforResourcePolicy($BitsreamPolicyID,ANONYMOUS_GROUP_ID);
															$embargoDateArray[] =  array("op" => "replace",
															"path"=> "/startDate","value" => $embargoEndDate);
																
														    $embargoDate = json_encode($embargoDateArray);
															$response = dspaceUpdateResourcePolicy($BitsreamPolicyID,$embargoDate);
															if($response['status'] == 'success')
															{
																echo '-- one year embargo added for '.$uuid.PHP_EOL;
																$report.= '-- embargo end date failed to be added for  '.$uuid.PHP_EOL;
															}
															if($response['status'] == 'failed')
															{
																echo '-- embargo end date failed to be added for bistream for the item  '.$uuid.PHP_EOL;
																print_r($response);
															} 
															
															
															
														}
														
													}
													else
													{
														//add resource policy 
														$bodyArray =  array("startDate" => $embargoEndDate,
														"action" => "READ");
														$body = json_encode($bodyArray);
														//print_r($body);
														$response =dspaceCreateResourcePolicy($BitstreamID, 'group', ANONYMOUS_GROUP_ID, $body);
														//print_r($response);
														if($response['status'] == 'success')
														{
															echo '-- Policy added for '.$uuid.PHP_EOL;
																	
														}
														if($response['status'] == 'failed')
														{
															echo '-- Policy failed to be added for  item '.$uuid.PHP_EOL;
															print_r($response);
														}
													}
													
												}
													
											}
										}
									}
								} 
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
			}
			else
			{
				print_r($response);
				sleep(5);
			}
		}
		else
		{
			echo 'collectionUUID is required';
		}
		
		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	
	}
										
								