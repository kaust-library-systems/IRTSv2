<?php
	//Define function to update the simple item display for repository records
	function updateRepositoryItemDisplay($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{			
			//custom query to use instead of base query
			if(isset($_GET['customQuery']))
			{
				$customQuery = urlencode($_GET['customQuery']);

				$report .= 'Custom Query: '.$customQuery.PHP_EOL;

				$baseQuery = 'query='.$customQuery.'&sort=dc.date.accessioned,desc&size=40';
				//$baseQuery = 'query='.$customQuery;
			}
			else
			{
				//base query - search for discoverable items, sorted by date added, oldest first
				$baseQuery = 'query=discoverable:true%20AND%20NOT%20display.details.left:*&sort=dc.date.accessioned,desc&dsoType=item';
				//$baseQuery = 'size=1';
				//$baseQuery = '';
			}
		
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

					$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

					echo $totalPages.PHP_EOL;
					
					foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
					{
						$item = $result['_embedded']['indexableObject'];
						
						$uuid = $item['uuid'];

						echo $uuid.PHP_EOL;
						
						if(isset($item['metadata']['dc.identifier.doi']))
						{
							$doi = $item['metadata']['dc.identifier.doi'][0]['value'];

							if(!empty($doi))
							{
								$idInIRTS = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'), 'singleValue');
							}
						}

						$type = $item['metadata']['dc.type'][0]['value'];

						$metadata = [];

						$kaustAuthors = [];

						if(isset($item['metadata']['kaust.person']))
						{
							foreach($item['metadata']['kaust.person'] as $kaustAuthor)
							{
								$kaustAuthors[] = $kaustAuthor['value'];
							}
						}

						$orcidAuthors = [];
						$orcidAdvisors = [];
						$orcidCommitteeMembers = [];

						foreach($item['metadata'] as $field => $entries)
						{
							foreach($entries as $place => $entry)
							{
								$orcid = '';
								
								$newEntry = [];

								$newEntry['value'] = $entry['value'];

								if(!empty($entry['authority']))
								{
									$authority = $entry['authority'];
									
									$orcid = getValues($irts, "SELECT m2.`value` FROM `metadata` m
										LEFT JOIN metadata m2 ON m.parentRowID = m2.parentRowID
										WHERE m.`source` LIKE 'repository'
										AND m.`field` LIKE 'dspace.authority.key'
										AND m.value LIKE '$authority'
										AND m2.`source` LIKE 'repository'
										AND m2.`field` LIKE 'dc.identifier.orcid'", array('value'), 'singleValue');
								}

								if($field == 'dc.contributor.author')
								{
									if(empty($orcid && in_array($entry['value'], $kaustAuthors)))
									{
										$match = checkPerson(array('name'=>$entry['value']));
										//print_r($match);

										if(!empty($match['localID']))
										{
											//add ORCID from local person match
											if(!empty($match['orcid']))
											{
												$orcid = $match['orcid'];
											}
										}
									}

									if(empty($orcid && in_array($entry['value'], $kaustAuthors)))
									{
										$match = checkPerson(array('name'=>$entry['value']));
										//print_r($match);

										if(!empty($match['localID']))
										{
											//add ORCID from local person match
											if(!empty($match['orcid']))
											{
												$orcid = $match['orcid'];
											}
										}
									}

									//check in IRTS for ORCID
									if(empty($orcid) && !empty($idInIRTS))
									{
										$orcid = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$entry['value']), 'dc.identifier.orcid'), array('value'), 'singleValue');
									}

									if(empty($orcid) && in_array($type, ['Thesis', 'Dissertation']))
									{
										$match = checkPerson(array('name'=>$entry['value']));
										//print_r($match);

										if(!empty($match['localID']))
										{
											//add ORCID from local person match
											if(!empty($match['orcid']))
											{
												$orcid = $match['orcid'];
											}
										}
										
										if(empty($orcid))
										{
											// original ORCID entry in form may be marked deleted but it should still be correct
											$orcid = getValues($irts, "SELECT `value` FROM `metadata` 
											WHERE `source` LIKE 'dspace'
											AND `idInSource` LIKE '$uuid'
											AND `field` LIKE 'dc.identifier.orcid'", array('value'), 'singleValue');

											//remove ORCID prefix if present
											if(strpos($orcid,'https://orcid.org/') !== FALSE)
											{
												$orcid = str_replace('https://orcid.org/', '', $orcid);   
											}
										}
									}
									
									if(!empty($orcid))
									{
										$orcidAuthors[$place] = $entry['value'] . '::' . $orcid;
									}
									else
									{
										//keep author names even when there is no ORCID so that place stays consistent with dc.contributor.author
										$orcidAuthors[$place] = $entry['value'];
									}
								}

								if($field == 'dc.contributor.advisor' || $field == 'dc.contributor.committeemember')
								{
									if(empty($orcid))
									{
										$match = checkPerson(array('name'=>$entry['value']));
										//print_r($match);

										if(!empty($match['localID']))
										{
											//only accept match if person is faculty
											$profTitle = getValues($irts, "SELECT value
											FROM metadata
											WHERE source = 'local'
											AND idInSource = '".$match['localID']."'
											AND field = 'local.employment.type'
											AND value LIKE 'Faculty'
											AND deleted IS NULL", array('value'), 'singleValue');

											if(!empty($profTitle))
											{
												//add ORCID from local person match
												if(!empty($match['orcid']))
												{
													$orcid = $match['orcid'];
												}
											}
										}
									}

									if(!empty($orcid))
									{
										if($field == 'dc.contributor.advisor')
										{
											$orcidAdvisors[$place] = $entry['value'] . '::' . $orcid;
										}
										elseif($field == 'dc.contributor.committeemember')
										{
											$orcidCommitteeMembers[$place] = $entry['value'] . '::' . $orcid;
										}
									}
									else
									{
										//keep advisor and committee member names even when there is no ORCID so that place stays consistent with dc.contributor.advisor and dc.contributor.committeemember
										if($field == 'dc.contributor.advisor')
										{
											$orcidAdvisors[$place] = $entry['value'];
										}
										elseif($field == 'dc.contributor.committeemember')
										{
											$orcidCommitteeMembers[$place] = $entry['value'];
										}
									}
								}

								if(!empty($orcid))
								{
									$newEntry['children']['dc.identifier.orcid'][]['value'] = $orcid;
								}

								$metadata[$field][] = $newEntry;
							}
						}

						$patches = [];

						//remove dc.identifier.orcid field, this will only be used during submission for ETDs
						if(isset($metadata['dc.identifier.orcid']))
						{
							unset($metadata['dc.identifier.orcid']);
							
							$patches[] = array("op" => "remove",
										"path" => "/metadata/dc.identifier.orcid");
						}
						
						$result = setDisplayFields($metadata);
						
						$patches = array_merge($patches, $result['patch']);
	
						if(!empty($orcidAuthors))
						{
							if(isset($metadata['orcid.author']))
							{
								unset($metadata['orcid.author']);
								
								$patches[] = array("op" => "remove",
											"path" => "/metadata/orcid.author");
							}
							
							foreach($orcidAuthors as $place => $orcidAuthor)
							{
								$patches[] = array("op" => "add",
								"path" => "/metadata/orcid.author/-",
								"value" => array("value" => $orcidAuthor));
							}
						}

						if(!empty($orcidAdvisors))
						{
							if(isset($metadata['orcid.advisor']))
							{
								unset($metadata['orcid.advisor']);
								
								$patches[] = array("op" => "remove",
											"path" => "/metadata/orcid.advisor");
							}
							
							foreach($orcidAdvisors as $place => $orcidAdvisor)
							{
								$patches[] = array("op" => "add",
								"path" => "/metadata/orcid.advisor/-",
								"value" => array("value" => $orcidAdvisor));
							}
						}

						if(!empty($orcidCommitteeMembers))
						{
							if(isset($metadata['orcid.committeemember']))
							{
								unset($metadata['orcid.committeemember']);
								
								$patches[] = array("op" => "remove",
											"path" => "/metadata/orcid.committeemember");
							}
							
							foreach($orcidCommitteeMembers as $place => $orcidCommitteeMember)
							{
								$patches[] = array("op" => "add",
								"path" => "/metadata/orcid.committeemember/-",
								"value" => array("value" => $orcidCommitteeMember));
							}
						}

						$patchJSON = json_encode($patches);

						//echo $patchJSON;
						
						$response = dspacePatchMetadata('items', $uuid, $patchJSON);
						
						echo $response['status'].PHP_EOL;

						//try to log in again if failed, normally the tokens just need to be refreshed
						if($response['status'] == 'failed')
						{
							print_r($response);

							print_r($patchJSON);
							
							/* if($response['error']=='Response code received (100) does not match expected response code (200)')
							{
								//patch succeeds on 100 code
							}
							else
							{
								//Get initial CSRF token and set in session
								$response = dspaceGetStatus();
										
								//Log in
								$response = dspaceLogin();
	
								$response = dspacePatchMetadata('items', $uuid, $patchJSON);
							
								echo $response['status'].PHP_EOL;
		
								if($response['status'] == 'failed')
								{
									print_r($response);
		
									//Get initial CSRF token and set in session
									$response = dspaceGetStatus();
											
									//Log in
									$response = dspaceLogin();
	
									//stop after first failed patch
									//$continuePaging = FALSE;
								}
							}		 */					
						}

						set_time_limit(0);
						ob_flush();

						//sleep(5);
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
					print_r($response);
					
					sleep(5);

					$continuePaging = FALSE;
				}

				//break after 1st query
				//$continuePaging = FALSE;
			}
		}

		$summary = saveReport($irts,__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
