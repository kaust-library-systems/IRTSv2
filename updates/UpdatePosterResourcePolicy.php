<?php
	//Define function to update the access policy for repository records
	function UpdatePosterResourcePolicy($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$response = dspaceGetStatus();
					
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{
			$CollectionsList = dspaceListCommunityCollections(CurrentPosterSession_community_UUID);
			
			$CollectionsList = json_decode($CollectionsList['body'], TRUE);
		   
			foreach ($CollectionsList['_embedded']as $Collections)
			{
				foreach ($Collections as $Collection)
				{
					
					$CollectionUUID= $Collection['uuid'];
					
					$CollectionInfo = dspaceGetCollectionMetadata($CollectionUUID);
					$CollectionInfo = json_decode($CollectionInfo['body'], TRUE);
					
					$CollectionMetadata = $CollectionInfo['metadata'];

					if(isset($CollectionMetadata['dc.conference.date'][0]['value']))
					{
						$CollectionIssueDate = $CollectionMetadata['dc.conference.date'][0]['value'];
						if(ONE_MONTH_AGO > $CollectionIssueDate)
						{
							$CollectionName = $Collection['name'];
						    //$CollectionDate = $Collection['dc.conference.date'][0]['value'];
						    echo 'The Conference date is '.$CollectionIssueDate.' for collection '.$Collection['name'].'  and the collection id '. 
							$CollectionUUID.PHP_EOL;
							
							$report.='The Conference date is'.$CollectionIssueDate.'for collection'.$Collection['name'].'and the collection id'.$CollectionUUID.PHP_EOL; 
							
							
							$NewCollectionMetadata = array("name" => $CollectionName,
							"metadata"=> array("dc.title"=>array(array("value"=>$CollectionName)),"dc.conference.date"=>array(array("value"=>$CollectionIssueDate ))));
							$NewCollectionMetadata = json_encode($NewCollectionMetadata);
							
							// new collection under Event Community								
							$NewCollection = dspaceCreateCollection(EventsCommunityUUID,
							$NewCollectionMetadata);
							
							if ($NewCollection['status'] == 'success')
							{
								$NewCollection= json_decode($NewCollection['body'], TRUE);
								$GetNewCollection = dspaceGetCollectionMetadata($NewCollection['uuid']);
								$GetNewCollection = json_decode($GetNewCollection['body'], TRUE);
								$GetNewCollectionUUID = $GetNewCollection['uuid'];
								$baseQuery = 'scope='.$CollectionUUID; // set scope based on collection UUID
								$page = 0;
								
								$report.='New collection has been created and the items of collection '.$Collection['name'].' are being moved to the new collection '.$GetNewCollectionUUID.PHP_EOL;
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
									$Search = dspaceSearch($query);
									if($Search['status'] == 'success')
									{
										$results = json_decode($Search['body'], TRUE);
										$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];
										echo 'this is no of total pages '.$totalPages.PHP_EOL;
										
										foreach($results['_embedded']['searchResult']['_embedded']['objects']as $result)
										{ 
										   $recordTypeCounts['all']++;
										   $item = $result['_embedded']['indexableObject'];
										   $uuid = $item['uuid'];
										   echo $uuid.PHP_EOL;
										   
										   
										   
											//move records to new collection 
										   $MoveCollection = dspaceMoveItem($uuid, $GetNewCollectionUUID); 
											
										   $metadata =$item['metadata'];
										   $record = dSpaceMetadataToArray($metadata);
	
										   foreach ($item as $value)
										   //foreach($record['dc.description.provenance']as $value)
										    { 
											   
												if(isset($record['dc.rights.embargodate']))
												{
													$flage= 'availabilitySelectionEmbargo';
												}
													
												else
												{ 
												   $flage= 'availabilitySelectionDark';
												}	
												
											}
											
											if ($flage ==='availabilitySelectionEmbargo')
											{
												// same as whatever on the metadata 
												//print_r($record);
												$embargoEndDate = $record['dc.rights.embargodate'][0]; 
												echo $embargoEndDate.PHP_EOL;
												$ItemPolicy = dspaceResourcePolicies($uuid);
												$ItemPolicy = json_decode($ItemPolicy['body'], TRUE);
												if(isset(($ItemPolicy['_embedded']['resourcepolicies'])))
												{
													foreach (($ItemPolicy['_embedded']['resourcepolicies'])as $ItemPolicy)
													{
														$ItemPolicyID = $ItemPolicy['id']; 
														$UpdatePolicy = dspaceUpdateGroupforResourcePolicy($ItemPolicyID,ANONYMOUS_GROUP_ID);
														//add start date to the policy 
														if(!isset($Policy['startDate']))
														{
															$embargoDateArray= [];
															$embargoDateArray[] =  array("op" => "add",
															"path"=> "/startDate",
															"value" => $embargoEndDate);
															$embargoDate = json_encode($embargoDateArray);
															$response = dspaceUpdateResourcePolicy($ItemPolicyID,$embargoDate);
															//echo $response['status'].PHP_EOL;
															if($response['status'] == 'success')
															{
																echo '-- one year embargo added for '.$uuid.PHP_EOL;
															}		
															if($response['status'] == 'failed')
															{
																echo '-- embargo end date failed to be added for  '.$uuid.PHP_EOL;
																print_r($response);
																$report.= '-- embargo end date failed to be added for  '.$uuid.PHP_EOL.print_r($response);
															}
														}
														else
														{
															echo '-- one year embargo already set for '.$uuid.'The embargo end date is'.$Policy['startDate'];
														} 
													}
													$recordTypeCounts['changed']++;
												} 
												// add start date to bitstream 
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
															//$UpdatePolicy = dspaceUpdateGroupforResourcePolicy($BitsreamPolicy,ANONYMOUS_GROUP_ID);
															$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);
															if(isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
															{
																foreach (($BitsreamPolicy['_embedded']['resourcepolicies'])as $Policy)
																{
																	$BitsreamPolicyID= $Policy['id'];
																	$UpdatePolicy = dspaceUpdateGroupforResourcePolicy($BitsreamPolicyID,ANONYMOUS_GROUP_ID);
																	if(!isset($BitsreamPolicy['startDate']))
																	{
																		$embargoDateArray= [];
																		$embargoDateArray[] =  array("op" => "add",
																		"path"=> "/startDate","value" => $embargoEndDate);
																		$embargoDate = json_encode($embargoDateArray);
																		$response = dspaceUpdateResourcePolicy($BitsreamPolicyID,$embargoDate);
																		if($response['status'] == 'success')
																		{
																			echo '-- one year embargo added for '.$uuid.PHP_EOL;
																			$report.= '-- one year embargo added for  '.$uuid.' bitstream'.PHP_EOL;
																			
																		}
																		if($response['status'] == 'failed')
																		{
																			echo '-- embargo end date failed to be added for  '.$uuid.PHP_EOL;
																			print_r($response);
																		}
																	}
																	else
																	{
																		echo '-- one year embargo already there for '.$uuid.'The embargo end date is'.$Policy['startDate'].PHP_EOL;
																		$recordTypeCounts['changed']++;
																	}
																}
																$recordTypeCounts['changed']++;
															}
														}
													} 
												}  
																								
											}
											if ($flage ==='availabilitySelectionDark')
											{
													
												$ItemPolicy = dspaceResourcePolicies($uuid);
												$ItemPolicy = json_decode($ItemPolicy['body'], TRUE);
												if(isset(($ItemPolicy['_embedded']['resourcepolicies'])))
												{
													foreach (($ItemPolicy['_embedded']['resourcepolicies'])as $Policy)
													{
														$PolicyID = $Policy['id'];
														$DeletePolicy = dspaceDeleteResourcePolicy($PolicyID);
													}
												} 
												//delete bitstream policy 
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
															$BitstreamName = $Bitstream['name'];$BitstreamID = $Bitstream['id'];
															$BitsreamPolicy = dspaceResourcePolicies($BitstreamID);
															$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);
															if(isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
															{
																foreach(($BitsreamPolicy['_embedded']['resourcepolicies'])as $Policy)
																{
																	$PolicyID = $Policy['id'];
																	$DeletePolicy = dspaceDeleteResourcePolicy($PolicyID);
																}
															}
														}
													}
												} 
												echo '-- dark archive --plociy deleted for'.$uuid.PHP_EOL;
												$recordTypeCounts['changed']++;
											    set_time_limit(0);
											    ob_flush();
												
											}
											
										 	
											
										}
										if(!isset($results['_embedded']['searchResult']['_links']['next']))
										{
											$continuePaging = FALSE;
										}
										 else
										{
										     echo 'no of pages '.$page;
											if($page > $totalPages)

											{
												$continuePaging = FALSE;
												$page++;
											}
											
																						
										}
																				
										
									}
									else
									{
										print_r($response);
										sleep(5);
										//$continuePaging = FALSE;
									}
									
								}
							}
							$Search = dspaceSearch('scope='.$CollectionUUID);
							$results = json_decode($Search['body'], TRUE);
							$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];
							if($totalPages == 0)
							{
								$response = dspaceDeleteCollection($CollectionUUID);
								 echo 'The collection  '.$Collection['name'].' collection id '. 
								 $CollectionUUID.' is deleted'.PHP_EOL;
								 $report.= 'The collection  '.$Collection['name'].' collection id '.$CollectionUUID.' is deleted'.PHP_EOL;
								print_r($response);
							}   
						}
					}
					
				}
				
			}		
		}
		else
		{
		    print_r($response);
			sleep(5);
		}

		$summary = saveReport( $irts,__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
