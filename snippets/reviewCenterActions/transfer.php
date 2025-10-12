<?php
	$transferPageLoadTime = microtime(true);

	$itemID = NULL;

	if(isset($_POST['handle']) )
	{
		$handle = $_POST['handle'];

		$handleURL = 'http://hdl.handle.net/'.$handle;
		
		if(!isset($_POST['itemID']))
		{
			$response = dspaceGetItemByHandle($handle);

			if($response['status'] == 'success')
			{
				$item = json_decode($response['body'], TRUE);
					
				$itemID = $item['uuid'];

				//echo $itemID;
			}
			else
			{
				$message .= ' - Failed to find the repository item at <a href="'.$handleURL.'">'.$handleURL.'</a><br>'.print_r($response, TRUE);
			}
		}
		else
		{
			$itemID = $_POST['itemID'];
		}
	}

	if(isset($_POST['transferType']))
	{
		if($_POST['transferType'] === 'addFileByURL')
		{
			if(empty($_POST['fileURLs']))
			{
				if($_GET['itemType']==='Unpaywall')
				{
					$urls = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, array('unpaywall.relation.url')), array('value'));
				}
				else
				{
					$urls = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, array('dc.relation.url','unpaywall.relation.url')), array('value'));
				}

				echo '<b>Select the URL for the PDF file that you would like to transfer:</b>
				<br><br> -- NOTE: The selected link must point directly to a PDF document file without passing through redirects or requiring cookies, etc. -- <br><br>

				<form method="post" action="reviewCenter.php?'.$selections.'">
					<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
					<input type="hidden" name="itemID" value="'.$itemID.'">';

				foreach($urls as $url)
				{
					echo '<input type="checkbox" name="fileURLs[]" value="'.$url.'"> '.$url.'</input><br>';
				}

				echo '<br>OR<br><br>
					<b>Enter the URL for the PDF file that you would like to transfer:</b>
					<br><br> -- NOTE: The entered link must point directly to a PDF document file without passing through redirects or requiring cookies, etc. -- 	<br><br>
					<textarea class="form-control" rows="1" name="fileURLs[]"></textarea>
					<input type="hidden" name="transferType" value="addFileByURL">
					<input type="hidden" name="handle" value="'.$handle.'">
					<button class="btn btn-lg btn-warning" type="submit" name="action" value="transfer">Add PDF file to item based on file URL</button>
				</form>';
			}
			else
			{
				$type = getValues($irts, setSourceMetadataQuery('dspace', $itemID, NULL, 'dc.type'), array('value'), 'singleValue');

				$version = getValues($irts, setSourceMetadataQuery('dspace', $itemID, NULL, 'dc.eprint.version'), array('value'), 'singleValue');

				$count = 1;

				if(count($_POST['fileURLs']) === 1 && empty($_POST['fileURLs'][0]))
				{
					echo "<br><br> -- You didn't select any file <br><br>";
				}
				else
				{
					$response = dspaceListItemBundles($itemID);

					//print_r($response);
					
					$bundleUUID = '';
					
					if($response['status'] == 'success')
					{
						$bundles = json_decode($response['body'], TRUE);

						foreach($bundles['_embedded']['bundles'] as $bundle)
						{ 
							if($bundle['name'] == 'ORIGINAL')
							{
								$bundleUUID = $bundle['uuid'];

								$message .= PHP_EOL.'Existing Bundle UUID: '.$bundleUUID;
							}
						}
					}
					else
					{
						//output error details in details tag
						$message .= '<div class="alert alert-danger">Failed to retrieve list of existing bundles, details below: 
							<details>
								<summary> - Failure Response: </summary>
								<pre> - '.print_r($response, TRUE).'</pre>
							</details>
						</div>';
					}
					
					if(empty($bundleUUID))
					{
						$bundle = array("name" => "ORIGINAL");
										
						$bundle = json_encode($bundle);
								
						$response = dspaceCreateItemBundle($itemID, $bundle);

						//print_r($response);

						if($response['status'] == 'success')
						{
							$newBundle = json_decode($response['body'], TRUE);
							
							//print_r($newBundle);

							$bundleUUID = $newBundle['uuid'];

							$message .= PHP_EOL.'New Bundle UUID: '.$bundleUUID;
						}
						else
						{
							//output error details in details tag
							$message .= '<div class="alert alert-danger">Failed to create bundle, details below: 
								<details>
									<summary> - Failure Response: </summary>
									<pre> - '.print_r($response, TRUE).'</pre>
								</details>
							</div>';
						}
					}

					if(!empty($bundleUUID))
					{
						foreach($_POST['fileURLs'] as $fileURL)
						{
							$message .= PHP_EOL.'File URL: '.$fileURL;
							
							$fileURL = str_replace(' ', '', $fileURL);

							if(!empty($fileURL))
							{
								$fileURL = str_replace('https://','http://',$fileURL);

								//$name = $type.'file'.$count.'.pdf';

								if(empty($version))
								{
									$description = $type;
								}
								else
								{
									$description = $version;
								}

								$context = stream_context_create(
									array(
										"http" => array(
											"header" => "User-Agent: KAUST Institutional Research Tracking Service"
										)
									)
								);

								$file = file_get_contents($fileURL, FALSE, $context);

								$urlParts = explode('/', $fileURL);
								$fileName = array_pop($urlParts);

								$filePath = UPLOAD_FILE_PATH.$fileName;

								file_put_contents($filePath, $file);
								//$file = file_get_contents($fileURL, FALSE, $context);
								
								$fileProperties = '{ 
									"name": "'.$fileName.'", 
									"metadata": { 
										"dc.description": [ { 
											"value": "'.$description.'", 
											"language": null, 
											"authority": null, 
											"confidence": -1, 
											"place": 0 } ]
										}, 
										"bundleName": "ORIGINAL" 
									}';

								$response = dspaceUploadBitstream($bundleUUID, $filePath, $fileProperties);

								if($response['status'] == 'success')
								{
									$bitstream = json_decode($response['body'], TRUE);
									
									//print_r($newBundle);

									$bitstreamUUID = $bitstream['uuid'];

									$message .= ' - File: <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstreamUUID.'/content">'.$fileName.'</a><br>';
									$message .= ' - Added to: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';

									// check if the record has an embargo date
									if(!empty($record['dc.rights.embargodate'][0]))
									{
										$embargoEndDate = $record['dc.rights.embargodate'][0];
										
										$response = dspaceResourcePolicies($bitstreamUUID);
										$bitstreamPolicies = json_decode($response['body'], TRUE);
										$bitstreamPolicies = $bitstreamPolicies['_embedded']['resourcepolicies'];
										
										foreach ($bitstreamPolicies as $bitstreamPolicy)
										{
											$policyID = $bitstreamPolicy['id'];
											$policyPatch[] =  array("op" => "add",
													"path"=> "/startDate",
													"value" => $embargoEndDate);
													
											$policyPatch= json_encode($policyPatch);
											$response = dspaceUpdateResourcePolicy($policyID, $policyPatch);

											if($response['status'] == 'success')
											{
												$message .= ' - With embargo until: '.$embargoEndDate.'<br>';
											}
											else
											{
												//output error details in details tag
												$message .= '<div class="alert alert-danger">Failed update file policy with embargo date, details below: 
													<details>
														<summary> - Failure Response: </summary>
														<pre> - '.print_r($response, TRUE).'</pre>
													</details>
												</div>';
											}
										}
									}
								}
								else
								{
									//output error details in details tag
									$message .= '<div class="alert alert-danger">Failed to upload file, details below: 
										<details>
											<summary> - Failure Response: </summary>
											<pre> - '.print_r($response, TRUE).'</pre>
										</details>
									</div>';
								}
							}
							$count++;
						}
					}
				}

				if(isset($selections) && $_GET['formType'] !== 'uploadFile')
				{
					$message .= '<hr>
						<div class="col-lg-6">
						<form method="post" action="reviewCenter.php?'.$selections.'">
							<input class="btn btn-lg btn-primary" type="submit" name="next" value="-- Start Next Item --"></input>
							</form>
						</div>';
				}
			}
		}
		else
		{
			$record = prepareRecordForTransfer($template, $idInIRTS);

			//echo '<br>Record:<br>'.print_r($record, TRUE);
			//echo '<br>Metadata:<br>'.print_r($metadata, TRUE);
			
			if(in_array($_POST['transferType'], array('createNewItem','createVersion','updateAllMetadata')))
			{				
				//get the owning collection for the item
				$result = determineOwningCollection($record);
				$owningCollectionID = $result['owningCollectionID'];
				$message .= $result['message'];
				
				//get existing metadata so that fields that should not be overwritten are kept
				if($_POST['transferType']==='updateAllMetadata')
				{
					$response = dspaceGetItem($itemID);

					if($response['status'] == 'success')
					{
						$item = json_decode($response['body'], TRUE);
						
						$existingMetadata = $item['metadata'];

						$record['dc.description.provenance'] = $existingMetadata['dc.description.provenance'];

						$record['dc.identifier.uri'] = $existingMetadata['dc.identifier.uri'];
					}
					else
					{
						//output error details in details tag
						$message .= '<div class="alert alert-danger">Failed to retrieve existing metadata for <a href="'.REPOSITORY_URL.'/items/'.$itemID.'">'.$itemID.'</a>, details below: 
							<details>
								<summary> - Failure Response: </summary>
								<pre> - '.print_r($response, TRUE).'</pre>
							</details>
						</div>';
					}
				}

				$record = appendProvenanceToMetadata($itemID, $record);

				$result = setDisplayFields($record);

				$record = $result['metadata'];

				//orcid.id will be kept for searching. orcid.author, etc. will be used for matching names to ORCIDs, dc.identifier.orcid is only used in setDisplayFields
				unset($record['dc.identifier.orcid']);
				
				usleep(10000);

				if($_POST['transferType']==='updateAllMetadata')
				{
					$itemJSON = dspacePrepareItem($record, $itemID);
					
					$response = dspaceUpdateItem($itemID, $itemJSON);
				}
				elseif($_POST['transferType']==='createVersion' && !empty($owningCollectionID))
				{
					$record['dc.identifier.uri'][] = $handleURL;
					
					$reasons = array();
					if(isset($_POST['reason']))
					{
						$reasons[] = $_POST['reason'];
					}

					if(!empty($_POST['other']))
					{
						$reasons[] = $_POST['other'];
					}
					$reason = implode('; ', $reasons);
					
					$response = dspaceVersionArchiveAndUpdateItem($itemID, $reason, $record, $owningCollectionID);
				}
				elseif($_POST['transferType']==='createNewItem' && !empty($owningCollectionID))
				{
					$itemJSON = dspacePrepareItem($record);
					
					//special handling for records with long author lists
					if(count($record['dc.contributor.author']) > 500)
					{
						$response = dspaceCreateRecordWithLongAuthorList($record, $owningCollectionID);
					}
					else
					{
						$response = dspaceCreateItem($owningCollectionID, $itemJSON);
					}
				}

				if($response['status'] == 'success')
				{
					if($_POST['transferType']==='updateAllMetadata')
					{
						$message .= PHP_EOL.'Metadata updated for: '.PHP_EOL.'- Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';
					}
					elseif($_POST['transferType']==='createVersion')
					{
						$itemID = $response['newVersionUUID'];
						
						$message .= ' - New version created at: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'">'.$itemID.'</a> with reason: '.$reason.'<br>';
						$message .= ' - Existing handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';
						$message .= ' - Full metadata page: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'/full">'.$itemID.'</a><br>';
					}
					elseif($_POST['transferType']==='createNewItem')
					{
						$item = json_decode($response['body'], TRUE);

						if(!empty($item['id']))
						{
							$itemID = $item['id'];
							$handle = $item['handle'];
							
							$message .= ' - New item ID: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'">'.$itemID.'</a><br>';
							$message .= ' - Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';
							$message .= ' - Full metadata page: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'/full">'.$itemID.'</a><br>';
						}
						else
						{
							//output error details in details tag
							$message .= '<div class="alert alert-success">Record may have been created, search the repository manually to confirm and find the handle.</div>
								<div class="alert alert-danger">No ID or handle received,  details below: 
								<details>
									<summary> - Failure Response: </summary>
									<pre> - '.print_r($response, TRUE).'</pre>
								</details>
								<details>
									<summary> - Posted JSON was: </summary>
									<pre> - '.$itemJSON.'</pre>
								</details>
							</div>';
						}
					}

					usleep(10000);

					//get the record of the item to save in the local database
					$response = dspaceGetItem($itemID);
					
					if($response['status'] == 'success')
					{
						$itemJSON = $response['body'];
						
						//save the sourceData in the database
						$result = saveSourceData($irts, 'dspace', $itemID, $itemJSON, 'JSON');

						$recordType = $result['recordType'];
						
						//process the record
						$result = processDspaceRecord($itemJSON);

						$record = $result['record'];

						$message .= $result['report'];
						
						//save it in the database
						$result = saveValues('dspace', $itemID, $record, NULL);

						$result = saveValues('repository', $handle, $record, NULL);
					}
					else
					{
						$message .= ' - Get Item Failure Response: '.print_r($response, TRUE).'<br> -- Failed to get metadata for item: '.$itemID;
					}
					
					//set inverse relationships on any related items
					$result = setInverseRelations($itemID);
					
					if(!empty($result))
					{
						$message .= PHP_EOL.' - Set Inverse Relations Result: '.$result.PHP_EOL;
					}
				}
				else
				{
					//output error details in details tag
					$message .= '<div class="alert alert-danger">Failed to '.$_POST['transferType'].', details below: 
						<details>
							<summary> - Failure Response: </summary>
							<pre> - '.print_r($response, TRUE).'</pre>
						</details>
						<details>
							<summary> - Item JSON was: </summary>
							<pre> - '.$itemJSON.'</pre>
						</details>
					</div>';
				}
			}
			
			//special message for unpaywall
			if($_GET['itemType'] === 'Unpaywall')
			{
				$message .= '<div class="alert alert-success" id="message"><p><b>Message</b></p><p>The rights metadata for this item has been updated in DSpace, but the selected Unpaywall file <a href='.$record['unpaywall.relation.url'][0].' >'.$record['unpaywall.relation.url'][0].'</a> has not yet been added to the repository. Select an option below to add the file.<p></div>';
			}
			else
			{
				$message .= '
					<br><b> -- If the file is not available via URL, please add it via direct upload to the DSpace item record.
					<br> -- If a manuscript request needs to be sent to authors, select that option below.</b>';
			}

			// show the buttons for options to add a file
			$message .= '<hr>
			<div class="col-lg-6">

			<form method="post" action="reviewCenter.php?'.$selections.'">
				<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
				<input type="hidden" name="itemID" value="'.$itemID.'">
				<input type="hidden" name="itemJSON" value="'.htmlspecialchars($itemJSON).'">
				<input type="hidden" name="handle" value="'.$handle.'">
				<input type="hidden" name="transferType" value="addFileByURL">
				<button class="btn btn-lg btn-warning" type="submit" name="action" value="transfer">-- Add PDF file to item based on file URL --</button>
			</form>

			<form method="post" action="reviewCenter.php?formType=uploadFile">
				<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
				<input type="hidden" name="itemID" value="'.$itemID.'">
				<input type="hidden" name="itemJSON" value="'.htmlspecialchars($itemJSON).'">
				<input type="hidden" name="handle" value="'.$handle.'">
				<input type="hidden" name="selections" value="'.$selections.'">
				<button class="btn btn-lg btn-secondary" type="submit" name="action" value="uploadFile" >-- Upload a file from desktop --</button>
			</form>
			';

			// In the Unpaywall process we do not need to show the send manuscript request button
			if($_GET['itemType'] !== 'Unpaywall')
			{
				$message .= '
				<form method="post" action="reviewCenter.php?'.$selections.'">
					<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
					<input type="hidden" name="handle" value="'.$handle.'">
					<input type="hidden" name="itemID" value="'.$itemID.'">
					<button class="btn btn-lg btn-success" type="submit" name="action" value="request">-- Send manuscript request -- </button>
				</form>';
			}
			
			$message .= '<form method="post" action="reviewCenter.php?'.$selections.'">
					<input class="btn btn-lg btn-primary" type="submit" name="next" value="-- Start Next Item --"></input>
					</form>
				</div>';
		}
	}

	echo $message;
	
	$transferPageLoadTime = microtime(true)-$transferPageLoadTime;

	insert($irts, 'messages', array('process', 'type', 'message'), array('transferPageLoadTime', 'report', $transferPageLoadTime.PHP_EOL.' seconds'.print_r($_POST, TRUE).PHP_EOL.print_r($_GET, TRUE)));