<?php
	//Define function to update ETD metadata, embargoes and mappings
	function manageETDs($report, $errors, $recordTypeCounts)
	{		
		global $irts;
		//Get initial CSRF token and set in session
				
		$response = dspaceGetStatus();
				
		$response = dspaceLogin();
		
		$unmatched = array();

		$failed = array();

		$recordTypeCounts['mapped'] = 0;

		$recordTypeCounts['skipped'] = 0;
		
		$recordTypeCounts['error sent to repository'] = 0;
		
		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		//CC repository email
		$headers .= "From: " .IR_EMAIL. "\r\n";
		$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";
		
		if(isset($_GET['itemHandle']))
		{
			
			$report .= 'Item Handle:'.$_GET['itemHandle'].PHP_EOL;

			$handles = [$_GET['itemHandle']];
		}
		else
		{
			
			$query = "SELECT DISTINCT idInSource FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `field` LIKE 'dc.date.accessioned'
				AND `value` LIKE '".TODAY."%'
				AND `deleted` IS NULL
				AND idInSource IN (
					SELECT idInSource FROM metadata
					WHERE `source` LIKE 'repository'
					AND `field` LIKE 'dc.type'
					AND `value` in ('Dissertation','Thesis')
					AND `deleted` IS NULL
				)
				AND CONCAT('repository_',idInSource) NOT IN (
					SELECT idInSource FROM metadata
					WHERE source = 'irts'
					AND field = 'irts.checked.status'
					AND value = 'complete'
					AND added LIKE '".TODAY."%'
					AND deleted IS NULL
					AND parentRowID IN (
						SELECT rowID FROM metadata
						WHERE source = 'irts'
						AND field = 'irts.checked.process'
						AND value = 'manageETDs'
						AND deleted IS NULL
					)
				)"; 
 
			$handles = getValues($irts, $query, array('idInSource'));
		}
		

		foreach ($handles as $itemHandle)
		{
			$search = dspaceGetItemByHandle($itemHandle);

		     $flags = array();

		    $deptIDs = array();

		    $names = array();

		    $orcids = array();

		    $deptCollectionIDs = array();

		    $changed = FALSE;
		    $embargoSet = FALSE;

		    $recordTypeCounts['all']++;
            $itemReport = '';
	        $item = json_decode($search['body'], TRUE);
		
		    $itemID = $item['id'];
			
			//print($itemID);
			
			
			$itemMetadata =$item['metadata'];
			
			$record = dSpaceMetadataToArray($itemMetadata);
			//print_r($record);
			 
			if(isset($record['dc.contributor.author']))
			{
				foreach($record['dc.contributor.author'] as $place => $value)
				{
					//If last name is separated by comma, but not space, from first name, fix it.
					if(strpos($value, ', ')===FALSE&&strpos($value, ',')!==FALSE)
					{
						$value = str_replace(',', ', ', $value);
					}
					
					if(isset($record['dc.identifier.orcid']))
					{
						if(strpos($record['dc.identifier.orcid'][0],'https')!==FALSE)
						{
							$orcid = str_replace('https://orcid.org/', '', $record['dc.identifier.orcid'][0]);   
						}
						else
						{
							$orcid = $record['dc.identifier.orcid'][0];
						}
							
						$record['dc.identifier.orcid'][$place] = $orcid;
					
						$flags[] = 'orcidAddedToStudentAuthor';		
					}
				}
			}
			
			if(isset($record['dc.contributor.advisor']))
			{
				foreach($record['dc.contributor.advisor'] as $place => $value)
				{
					if(strpos($value, ', ')===FALSE&&strpos($value, ',')!==FALSE)
					{
						$value = str_replace(',', ', ', $value);
					}

					//check by name for ORCID 
					if(strpos($value, '::')===FALSE)
					{
						$match = checkPerson(array('name'=>$value));
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
								if(!empty($match['orcid']))
								{
									$record['dc.identifier.orcid']['dc.contributor.advisor'][$place] = $match['orcid'];
										
									$flags[] = 'orcidAddedToAdvisor';
								}
							}
						}
						else
						{
							$itemReport .= 'No local person ID found for faculty advisor name: '.$value.PHP_EOL;

							$flags[] = 'mismatch';
						}
					}

				}
			}
				if(isset($record['dc.contributor.committeemember']))
				{
					foreach($record['dc.contributor.committeemember'] as $place => $value)
					{
						
						//If last name is separated by comma, but not space, from first name, fix it.
						if(strpos($value, ', ')===FALSE&&strpos($value, ',')!==FALSE)
						{
							$value = str_replace(',', ', ', $value);
						}

						//Only check by name if no ORCID is attached
						if(strpos($value, '::')===FALSE)
						{
							$match = checkPerson(array('name'=>$value));
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
									if(!empty($match['orcid']))
									{
									
										$record['dc.identifier.orcid']['dc.contributor.committeemember'][$place] = $match['orcid'];
									
										$flags[] = 'orcidAddedToCommitteeMember';
									}
								}
							}
						}
					}

				}
			
 
			
				/* if(isset($record['dc.contributor.department']))
				{
					foreach($record['dc.contributor.department'] as $place => $value)
					{
						//only interested in dept ids for departments that have collections
						$deptID = getValues($irts,
						"SELECT idInSource FROM metadata
						WHERE source = 'local'
						AND field IN ('local.org.name', 'local.name.variant')
						AND value = '$value'
						AND idInSource IN (
							SELECT idInSource FROM metadata
							WHERE source = 'local'
							AND field = 'dspace.collection.handle'
							AND deleted IS NULL)
						AND deleted IS NULL", array('idInSource'), 'singleValue');

						if(!empty($deptID))
						{
							$deptHandle = getValues($irts, setSourceMetadataQuery('local', $deptID, NULL, 'dspace.collection.handle'), array('value'), 'singleValue');

							//$itemReport .= $deptHandle.'<br>';

							if(!empty($deptHandle))
							{
								
								$collectionID = getValues($irts, setSourceMetadataQuery('dspace', NULL, NULL, 'dspace.collection.handle', $deptHandle), array('idInSource'), 'singleValue');
								
								
								$deptCollection= dspaceSearch('query=handle:"'.$deptHandle.'"&dsoType=collection');

								
								
								$deptCollection= json_decode($deptCollection['body'], TRUE);
								//print_r($deptCollection);		
								
								$deptCollection= $deptCollection['_embedded']['searchResult']['_embedded']['objects'][0]['_embedded']['indexableObject'];
								
								$deptCollectionIDs[] = $deptCollection['id'];

								$controlledValue = getValues($irts, setSourceMetadataQuery('dspace', $collectionID, NULL, 'dspace.collection.name'), array('value'), 'singleValue');

								if($controlledValue !== $value)
								{
									$record['dc.contributor.department'][$place] = $controlledValue;
									print($controlledValue);

									$itemReport .= 'Dept name "'.$controlledValue.'" changed'.PHP_EOL;

									$flags[] = 'divisionNameChanged';
								}

								if(strpos($controlledValue, 'Division') === FALSE)
								{
									$itemReport .= 'Program name "'.$record['dc.contributor.department'][$place].'" removed'.PHP_EOL;

									unset($record['dc.contributor.department'][$place]);

									$flags[] = 'programNameRemovedFromDeptsList';
								}
							}
						}
						else
						{
							$itemReport .= 'No Dept ID found for matched org name variant "'.$value.'"'.PHP_EOL;

							$flags[] = 'mismatch';
						}
					}
				}

				if(isset($record['thesis.degree.discipline']))
				{
					foreach($record['thesis.degree.discipline'] as $place => $value)
					{
						//only interested in dept ids for departments that have collections
						$deptID = getValues($irts,
						"SELECT idInSource FROM metadata
						WHERE source = 'local'
						AND field IN ('local.org.name', 'local.name.variant')
						AND value = '$value'
						AND idInSource IN (
							SELECT idInSource FROM metadata
							WHERE source = 'local'
							AND field = 'dspace.collection.handle'
							AND deleted IS NULL)
						AND deleted IS NULL", array('idInSource'), 'singleValue');

						if(!empty($deptID))
						{
							$deptHandle = getValues($irts, setSourceMetadataQuery('local', $deptID, NULL, 'dspace.collection.handle'), array('value'), 'singleValue');

							//$itemReport .= $deptHandle.'<br>';

							if(!empty($deptHandle))
							{
								$collectionID = getValues($irts, setSourceMetadataQuery('dspace', NULL, NULL, 'dspace.collection.handle', $deptHandle), array('idInSource'), 'singleValue');

								
								$deptCollection= dspaceSearch('query=handle:"'.$deptHandle.'"&dsoType=collection');
								
								$deptCollection= json_decode($deptCollection['body'], TRUE);
								//print_r($deptCollection);		
								
								$deptCollection= $deptCollection['_embedded']['searchResult']['_embedded']['objects'][0]['_embedded']['indexableObject'];
								
								$deptCollectionIDs[] = $deptCollection['id'];
								
								//print_r($deptCollectionID);
								
								
								
								//The name of this program has changed, but previously issued ETDs should still carry the old program name and be mapped to the program collection
								if($value !== "Chemical and Biological Engineering")
								{
									if($controlledValue !== $value)
									{
										$controlledValue = getValues($irts, setSourceMetadataQuery('local', $deptID, NULL, 'local.org.name'), array('value'), 'singleValue');

										if($controlledValue !== $value)
										{
											$record['thesis.degree.discipline'][$place] = $controlledValue;

											$itemReport .= 'Program name "'.$controlledValue.'" changed'.PHP_EOL;

											$flags[] = 'programNameChanged';
										}
									}
								}
							}
						}
						else
						{
							$itemReport .= 'No Dept ID found for matched org name variant "'.$value.'"'.PHP_EOL;

							$flags[] = 'mismatch';
						}
					}
				} */

				
				
				/* if(isset($record['kaust.availability.selection']))
				{ */
					
					if(isset($record['dc.description.provenance']))
					{

						//first check if availability selection and license are already listed in the provenance, if so, they do not need to be added again
						$availabilitySelectionInProvenance = FALSE;
						$licenseInProvenance = FALSE;
						
						//keep list of seen provenance statements to identify duplicates
						$alreadySeen = [];
						
						foreach($record['dc.description.provenance'] as $place => $value)
						{							
							if(in_array($value, $alreadySeen))
							{
								unset($record['dc.description.provenance'][$place]);
								
								$flags[] = 'duplicate provenance statements removed';
							}
							else
							{
								$alreadySeen[] = $value;
							}								
							
							if(strpos($value, "Availability selection made by") !== FALSE)
							{
								$availabilitySelectionInProvenance = TRUE;
							}
							
							if(strpos($value, "License granted by") !== FALSE)
							{
								$licenseInProvenance = TRUE;
							}
						}
						
						//loop through again to get the submission info
						foreach($record['dc.description.provenance'] as $place => $value)
						{
							if(strpos($value, "Submitted by") !== FALSE)
							{
								$value = str_replace('Submitted by ', '', $value);
								$submissionInfo = explode(' workflow start', $value)[0];
								
								//only add availability selection if not already added in provenance
								if(!$availabilitySelectionInProvenance)
								{
									$availabilityStatement = 'Availability selection made by '.$submissionInfo.' (GMT). Selection: "'.$record['kaust.availability.selection'][0].'".';
								
									$record['dc.description.provenance'][] = $availabilityStatement;
									
									$flags[] = 'availability selection recorded in provenance';
								}
							}			
						}
						
					}
					
					$ItemBundles = dspaceListItemBundles($itemID);	
					$ItemBundles = json_decode($ItemBundles['body'], TRUE);

					foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
					{
						$ItemBundlesName = $ItemBundle['name'];

						if ($ItemBundlesName == 'ADMIN')
						{
							$adminBundleUUID = $ItemBundle['uuid'];
						}
					}

					//if no admin bundle exists, create one
					if(empty($adminBundleUUID))
					{
						$bundle = array("name" => "ADMIN");				
						$bundle =json_encode($bundle);		
						$adminBundle = dspaceCreateItemBundle($itemID,$bundle);
						$adminBundle = json_decode($adminBundle['body'], TRUE);
						$adminBundleUUID = $adminBundle['uuid'];
					}

					foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
					{
						$ItemBundlesName = $ItemBundle['name'];
						$ItemBundleID = $ItemBundle['uuid'];
						$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
						$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);
						
						
						if ($ItemBundlesName == 'LICENSE')
						{
							if(!$licenseInProvenance)
							{ 
						        $BitstreamID = $bundleBitstreams['_embedded']['bitstreams'][0]['id']; 
								//retrieve license text
							   $license =dspaceGetBitstreamsContent($BitstreamID);
							   $license =$license['body'];
								//add line break above agreement statement
							   $license .= '
								
							    ';
							   $license .= 'License granted by '.$submissionInfo.' (GMT).';
								
							   $record['dc.description.provenance'][] = $license;
							   $flags[] = 'license agreement recorded in provenance';

						    
								//print_r($license);
							}	
						}

						if ($ItemBundlesName == 'ORIGINAL')
						{
							foreach(($bundleBitstreams['_embedded']['bitstreams']) as $Bitstream)
							{
								$BitstreamName = $Bitstream['name'];
								$BitstreamID = $Bitstream['id'];
								$arr = array('form','result','email','approval','embargo', 'expiration',' reminder', 'copyright','results','screenshoot','result-form','Examination','PI' ,'Confirmation','advisor','DocuSign');
								//move admin forms to new bundle
								foreach ($arr as $value) 
								{
									if(strripos($BitstreamName, $value) !== FALSE)
									{
										$BitsreamPolicy = dspaceResourcePolicies($BitstreamID);$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);
										if (isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
										{
											foreach (($BitsreamPolicy['_embedded']['resourcepolicies'])as $Policy)
											{
												foreach (($BitsreamPolicy['_embedded']['resourcepolicies'])as $Policy)
												{
													$PolicyID = $Policy['id'];
													$DeletePolicy = dspaceDeleteResourcePolicy($PolicyID);
												}
											}
										}
										$MovedBitstream = dspaceMoveBitstream($BitstreamID, $adminBundleUUID);
									}
								}
							}
						}
					}

				if(strpos($record['kaust.availability.selection'][0], "Embargo") !== FALSE)
				{
					$embargoSet = TRUE;
				}
				else
				{
					echo 'Embargo not set: '.$record['kaust.availability.selection'][0].PHP_EOL;
					
					$embargoSet = FALSE;
				}

				if($embargoSet)
				{
					$ItemBundles = dspaceListItemBundles($itemID);	
					$ItemBundles = json_decode($ItemBundles['body'], TRUE);
					foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
					{
						$ItemBundlesName = $ItemBundle['name'];

						if ($ItemBundlesName == 'ORIGINAL')
						{
							$ItemBundleID = $ItemBundle['uuid'];
							$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
							$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);
							foreach(($bundleBitstreams['_embedded']['bitstreams']) as $Bitstream)
							{
								print_r($Bitstream);
								
								$BitstreamId = $Bitstream['id'];
								$BitsreamPolicy = dspaceResourcePolicies($BitstreamId);

								print_r($BitsreamPolicy);

								$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);

								if(isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
								{
									foreach (($BitsreamPolicy['_embedded']['resourcepolicies']) as $Policy)
									{
										print_r($Policy);
										
										$PolicyID = $Policy['id'];
										if (isset($Policy['startDate']))
										{
											$embargoDateArray[] =  array("op" => "replace","path"=> "/startDate",
											"value" => ONE_YEAR_LATER);
											$embargoDate = json_encode($embargoDateArray);
											$EmbargoEndDate = dspaceUpdateResourcePolicy($PolicyID,$embargoDate);
											if($EmbargoEndDate['status'] == 'success')
											{
												$flags[] = 'start date updated to bitstream policy';
											}
											else
											{
												$flags[] = 'failed';
														//print_r($EmbargoEndDate);
												$itemReport .= 'error response for patch request to add policy start date : '.print_r($EmbargoEndDate, TRUE);
														
												print_r($EmbargoEndDate);
										    }
										}
										else
										{
											$embargoDateArray[] =  array("op" => "add",
											"path"=> "/startDate",
											"value" => ONE_YEAR_LATER);
											$embargoDate = json_encode($embargoDateArray);
											
											$EmbargoEndDate = dspaceUpdateResourcePolicy($PolicyID,$embargoDate);
											if($EmbargoEndDate['status'] == 'success')
											{
												$flags[] = 'start date added to bitstream policy';
											}
											else
											{
												$flags[] = 'failed';
														//print_r($EmbargoEndDate);
												$itemReport .= 'error response for patch request to add policy start date : '.print_r($EmbargoEndDate, TRUE);
														
												print_r($EmbargoEndDate);
											}

										}
												
									}		
								}
							}
						}
					}
						
				}
			
				$ItemBundles = dspaceListItemBundles($itemID);	
				$ItemBundles = json_decode($ItemBundles['body'], TRUE);
				foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
				{
					$ItemBundlesName = $ItemBundle['name'];
					$ItemBundleID = $ItemBundle['uuid'];
				    $bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
					$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);
					
					if ($ItemBundlesName == 'ORIGINAL')
					{
						
					    foreach(($bundleBitstreams['_embedded']['bitstreams'])as $Bitstream)
						{
							$BitstreamName = $Bitstream['name'];
							$BitstreamID = $Bitstream['id'];
							
							$BitsreamPolicy = dspaceResourcePolicies($BitstreamID);
							$BitsreamPolicy = json_decode($BitsreamPolicy['body'], TRUE);
							if (isset(($BitsreamPolicy['_embedded']['resourcepolicies'])))
							{
								foreach ($BitsreamPolicy['_embedded']['resourcepolicies'] as $Policy)
								{
									//print_r($Policy);
									if(($Policy['startDate']))
									{
										$itemReport .= '-- embargo: '.$Policy['startDate'].PHP_EOL;
										if($Policy['startDate'] > TODAY)
											{
												$accessStatement = 'At the time of archiving, the student author of this '.strtolower($record['dc.type'][0]).' opted to temporarily restrict access to it. The full text of this '.strtolower($record['dc.type'][0]).' will become available to the public after the expiration of the embargo on '.$Policy['startDate'].'.';
											}
											else
											{
												
												$record['dc.date.available'][0] = $Policy['startDate'].'T00:00:00Z';
												$accessStatement = 'At the time of archiving, the student author of this '.strtolower($record['dc.type'][0]).' opted to temporarily restrict access to it. The full text of this '.strtolower($record['dc.type'][0]).' became available to the public after the expiration of the embargo on '.$Policy['startDate'].'.';
											}

											if(!isset($record['dc.rights.accessrights']))
											{
												$record['dc.rights.accessrights'][0] = $accessStatement;
												$flags[] = 'access statement added';
											}
											elseif($accessStatement !== $record['dc.rights.accessrights'][0])
											{
												$record['dc.rights.accessrights'][0] = $accessStatement;
												$flags[] = 'access statement updated';
											}
											
											if(isset($record['dc.rights.embargodate']))
											{
												if($Policy['startDate'] !== $record['dc.rights.embargodate'][0])
												{
													$record['dc.rights.embargodate'][0] = $Policy['startDate'];
													$flags[] = 'embargo date updated in metadata';
												}
											}
											else
											{
												$record['dc.rights.embargodate'][0] = $Policy['startDate'];
												$flags[] = 'embargo date added to metadata';
											}
									}
								}
							}	
						}
					}

					if ($ItemBundlesName == 'ADMIN')
					{
						$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);
						$BitstreamsCounts = $bundleBitstreams['page']['totalElements'];
								
						if ($BitstreamsCounts < 2)
						{
							$flags[] = 'failed';
														
							$itemReport .= '<p>The administrative forms for the '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0] .'" at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle.'</a>. were not moved to the ADMIN bundle please move them manually';
									
									
						}
						else
						{
							$flags[] = 'admin forms moved to new bundle';
						}
												
							
					} 
				}
				
				
				if(isset($record['dc.subject']))
				{
					if(count($record['dc.subject']) === 1 && strpos($record['dc.subject'][0], ',') !== FALSE)
					{
						$keywords = explode(',', $record['dc.subject'][0]);
						unset($record['dc.subject'][0]);
						
						foreach($keywords as $keyword)
						{
							$record['dc.subject'][] = trim($keyword);
						}
						$flags[] = 'keywords split by comma';
					}
				}
				
				
				$flags = array_unique($flags);
				
				$changes = array('orcidAddedToStudentAuthor','orcidAddedToAdvisor', 'orcidAddedToCommitteeMember','divisionNameChanged','programNameRemovedFromDeptsList','programNameChanged','duplicate provenance statements removed','availability selection recorded in provenance','license agreement recorded in provenance','admin forms moved to new bundle','start date updated to bitstream policy','start date added to bitstream policy','access statement added','access statement updated','embargo date updated in metadata', 'embargo date added to metadata','keywords split by comma');
				
				foreach($changes as $change)
				{
					if(array_search($change, $flags)!==FALSE)
					{
						$changed = TRUE;
					}
				}

				if($changed)
				{
					$record = appendProvenanceToMetadata($itemID, $record, __FUNCTION__.' - '.implode(', ', $flags));
					
					$result = setDisplayFields($record);
					$record = $result['metadata'];
					//print_r($record);
					$metadata = dspacePrepareItem($record, $itemID);
					//print_r($metadata) ;	
					
					$response = dspaceUpdateItem($itemID, $metadata);
					//print_r($response);
					if($response['status'] == 'success')
					{
						$flags[] = 'modified';
					}
					else
					{
						$flags[] = 'putMetadataFailed';
						$failed['putMetadata'][] = $itemHandle;
						$itemReport .= 'error response from DSpace REST API for put metadata request: '.print_r($response, TRUE);
					}
				}
				//Map Collection
				
				$parentCollection =dspaceGetMappedCollection($itemID);
				$parentCollection = json_decode($parentCollection['body'], TRUE); 
				$parentCollection = $parentCollection['_embedded']['mappedCollections'];
				$collectionID = array();
				 foreach($parentCollection  as $collection)
				{
					$collectionID[] = $collection['id'];
				}
				foreach($deptCollectionIDs as $deptCollectionID)
				{
					if(!in_array($deptCollectionID, $collectionID))
					{
						$collectionID[] = $deptCollectionID;

						$flags[] = 'mapToNewCollection';
					}
				}

				if(in_array('mapToNewCollection',$flags))
				{
					//map to new collections

					$response = dspaceMapCollections($itemID, $collectionID);

					if($response['status'] == 'success')
					{
						$flags[] = 'mapped';
					}
					else
					{
						$flags[] = 'mapToNewCollectionFailed';

						$flags[] = 'failed';

						$failed['mapToNewCollection'][] = $itemID;

						$itemReport .= 'error response from DSpace REST API for map item to collections request: '.print_r($response, TRUE);
					}
				}
				
			//save value
			
			$result = saveValue('irts', 'repository_'.$itemHandle, 'irts.checked.process', 1, __FUNCTION__, NULL);
				
			$parentRowID = $result['rowID'];

			$flags = array_unique($flags);

			if(array_search('mismatch', $flags)!==FALSE||array_search('failed', $flags)!==FALSE)
			{
				$value = 'incomplete';
				
				$to = IR_EMAIL; 

				$subject = "ETD metadata management incomplete";
				$message = '<p>The ETD metadata management and collection mapping process for'.$itemID.' was incomplete, see details below: '.PHP_EOL.$itemReport.'</p><p>Please check the errors and correct manually as needed.</p>';
 

				$message = '<p>The ETD metadata management and collection mapping process for http://hdl.handle.net/'.$itemHandle.' was incomplete, see details below: '.PHP_EOL.$itemReport.'</p><p>Please check the errors and correct manually as needed.</p>';
				//print($message);

				if(mail($to,$subject,$message,$headers))
				{
					$report .= ' - error notice sent to '.$to.PHP_EOL;

					$recordTypeCounts['error sent to repository']++;
				} 
				else
				{
					$recordTypeCounts['failed to send']++;
				}   
			}
			else
			{
				$value = 'complete';
			} 
			
			$result = saveValue('irts', 'repository_'.$itemHandle, 'irts.checked.status', 1, $value, $parentRowID);

			$parentRowID = $result['rowID'];
			
			//This helps us avoid rechecking items that have not changed
			if($result['status'] === 'unchanged')
			{
				$irts->query("UPDATE `metadata` SET `added`='".date("Y-m-d H:i:s")."' WHERE `rowID`='".$result['rowID']."'");
			}

			$place = 1;
			foreach($flags as $flag)
			{
				$result = saveValue('irts', 'repository_'.$itemHandle, 'irts.checked.flag', $place, $flag, $parentRowID);

				$place++;

				if(!isset($recordTypeCounts[$flag]))
				{
					$recordTypeCounts[$flag] = 1;
				}
				else
				{
					$recordTypeCounts[$flag]++;
				}
			}

			if(!empty($itemReport))
			{
				$itemReport = $itemHandle.PHP_EOL.$itemID.PHP_EOL.$itemReport;
				
				$result = saveValue('irts', 'repository_'.$itemHandle, 'irts.checked.report', 1, $itemReport, $parentRowID);

				$report .= $recordTypeCounts['all'].') '.$itemReport.PHP_EOL;
			}
			else
			{
				$recordTypeCounts['unchanged']++;
			}
		}
		
			
	

			ob_flush();
			set_time_limit(0);

		print_r($flags);		

		$report .= 'unmatched: '.print_r($unmatched, TRUE).PHP_EOL;

		$report .= 'failed: '.print_r($failed, TRUE).PHP_EOL;

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);


		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
