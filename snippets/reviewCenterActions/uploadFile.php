<?php
	//empty form variable
	$message = '<div class="container">';

	//if item must be identified based on handle or DOI first
	if(!isset($_POST['action']))
	{
		$itemID = '';

		if(!isset($_POST['findRecord']))
		{
			$message .= 'Identify the record that you would like to add files to.<br><hr>
				<form method="post" action="reviewCenter.php?formType=uploadFile">
					<div>
					<div class="form-group">
					<label for="doi">DOI:</label>
					<textarea class="form-control" rows="1" name="doi"></textarea>
					</div>
					<div class="form-group">
					<label for="arxivID">arXiv ID:</label>
					<textarea class="form-control" rows="1" name="arxivID"></textarea>
					</div>
					<div class="form-group">
					<label for="handle">Handle:</label>
					<textarea class="form-control" rows="1" name="handle"></textarea>
					</div>
					</div>
					<input class="btn btn-primary" type="submit" name="findRecord" value="Find Matching Record in DSpace"></input>
				</form>';
		}
		elseif(empty($itemID))
		{
			$report = '';
		
			$identifiers = [];
			
			if(!empty($_POST['doi']))
			{
				$identifiers[trim($_POST['doi'])] = 'dc.identifier.doi';
			}
			elseif(!empty($_POST['arxivID']))
			{
				$identifiers[trim($_POST['arxivID'])] = 'dc.identifier.arxivid';
			}
			elseif(!empty($_POST['handle']))
			{
				$handle = trim($_POST['handle']);

				//make sure we have the current metadata for the item (and for its current version)
				$response = dspaceGetItemByHandle($handle);

				if($response['status'] == 'success')
				{
					$item = json_decode($response['body'], TRUE);
				
					$itemID = $item['id'];

					//use handle from response as handle entered in form may have been in the form of the full URL
					$handle = $item['handle'];

					//save the sourceData in the database
					$result = saveSourceData($irts, 'dspace', $itemID, $response['body'], 'JSON');

					$recordType = $result['recordType'];
					
					//process item
					$result = processDspaceRecord($response['body']);
					
					$record = $result['record'];

					$message .= $result['report'];
					
					//save it in the database
					$result = saveValues('dspace', $itemID, $record, NULL);

					$result = saveValues('repository', $handle, $record, NULL);

					$identifierFields = array('dspace.handle','dc.identifier.doi','dc.identifier.arxivid','dc.identifier.eid','dc.identifier.wosut','dc.identifier.pmid','dc.identifier.pmcid','dc.identifier.ccdc','dc.identifier.github','dc.identifier.bioproject');
				
					foreach($identifierFields as $identifierField)
					{
						$id = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $identifierField), array('value'), 'singleValue');
						
						if(!empty($id))
						{
							$identifiers[$id] = $identifierField;
						}
					}
				}
			}
			else
			{
				echo 'no IDs were entered';
			}

			if(!empty($identifiers))
			{
				foreach($identifiers as $id => $idField)
				{			
					$idInIRTS = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, $idField, $id), array('idInSource'), 'singleValue');
					
					$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, $idField, $id), array('idInSource'), 'singleValue');
					
					if(empty($handle))
					{
						$itemID = getValues($irts, setSourceMetadataQuery('dspace', NULL, NULL, $idField, $id), array('idInSource'), 'singleValue');

						if(!empty($itemID))
						{
							$handle = getValues($irts, setSourceMetadataQuery('dspace', $itemID, NULL, 'dspace.handle', NULL), array('value'), 'singleValue');

							//if handle was for old version
							if(strpos($handle, '.') !== FALSE)
							{
								$handle = explode('.', $handle)[0];
							}
							
							//unset old itemID so we make sure we get the one for the current version based on the main handle
							unset($itemID);
						}
					}

					if(!empty($idInIRTS) && !empty($handle))
					{
						break;
					}
				}

				if(empty($itemID))
				{
					//make sure we have the item ID in DSpace
					$response = dspaceGetItemByHandle($handle);

					if($response['status'] == 'success')
					{
						$item = json_decode($response['body'], TRUE);
					
						$itemID = $item['id'];
					}
				}
			}
		}
	}
	else
	{
		$itemID = $_POST['itemID'];
		
		$item = json_decode($_POST['itemJSON'], TRUE);
	}

	//once DSpace item ID has been identified
	if(!empty($itemID))
	{
		//if existing rights fields should be updated
		if(isset($_POST['action']) && $_POST['action'] == 'updateRights')
		{
			$newMetadata = $record;

			//print_r($newMetadata);

			//patch DSpace record with new rights fields
			$result = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, $_SESSION['displayname']);

			if($result['status'] == 'modified')
			{
				$message .= '<div class="alert alert-success">Rights fields updated successfully.</div>';
			}
			else
			{
				//output error details in details tag
				$message .= '<div class="alert alert-danger">Error updating rights fields: 
					<details>
						<summary>Details</summary>
						<p>'.print_r($result, TRUE).'</p>
					</details>
				</div>';
			}

			$record = $result['record'];

			$item['metadata'] = $record;
		}
		
		//add repository links to message
		$message .= displayRepositoryLinks($itemID, $handle);

		//add item info to message
		$message .= displayItemInfo($item['metadata']);

		//line to separate sections
		$message .= '<hr>';

		//form with button to allow update of rights fields on existing record
		$form = '<form action="reviewCenter.php?formType=uploadFile" method="post">';

		//add rights fields
		$form .= addEntriesToForm($item['metadata'], '', 'rights', TRUE, array('dc.date.issued', 'dc.rights.embargolength', 'dc.publisher', 'dc.identifier.arxivid', 'unpaywall.relation.url'));

		$form .= '<input type="hidden" name="handle" value="'.$handle.'">';

		//if uploading based on DOI or handle, the item may not have an idInIRTS
		if(isset($_POST['idInIRTS']))
		{
			$idInIRTS = $_POST['idInIRTS'];
			$form .= '<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">';
		}

		$form .= '<input type="hidden" name="itemID" value="'.$itemID.'">';
		
		//if coming from processing form queue
		if(isset($_POST['selections']))
		{
			$form .= '<input type="hidden" name="selections" value="'.$_POST['selections'].'">';
		}

		//include the item in the form
		$form .= '<input type="hidden" name="itemJSON" value="'.htmlspecialchars(json_encode($item)).'">';

		$form .= '<input type="hidden" name="action" value="updateRights">';

		$form .= '<button class="btn btn-block btn-warning" type="submit" id="submit" name="update">Update the rights on this record</button>
			</form>';

		//instructions to edit rights fields before uploading if they don't match the file that will be uploaded
		$message .= '<div class="alert alert-warning">If the version, embargo or license on the record is not correct for the file that you are going to upload, please update the record before uploading the file.</div>';
			
		$message .= $form;

		//if existing file should be deleted
		if(isset($_POST['action']) && $_POST['action'] == 'deleteFile')
		{
			$message .= 'Existing file deletion report:';

			if(!empty($bitstreamUUID))
			{
				$response = dspaceDeleteBitstream($bitstreamUUID);

				//print_r($response);

				if($response['status'] == 'success')
				{
					$message .= '<br> --- Existing file with UUID '.$bitstreamUUID.' deleted.';

					$response = dspaceListItemBundles($itemID);
		
					if($response['status'] == 'success')
					{
						$bundles = json_decode($response['body'], TRUE);

						foreach($bundles['_embedded']['bundles'] as $bundle)
						{ 
							if(in_array($bundle['name'], ['TEXT','THUMBNAIL']))
							{
								$bundleUUID = $bundle['uuid'];

								$message .= '<br>-- '.$bundle['name'].' Bundle UUID: '.$bundleUUID;

								$response = dspaceListBundlesBitstreams($bundleUUID);

								if($response['status'] == 'success')
								{
									$bitstreams = json_decode($response['body'], TRUE);

									foreach($bitstreams['_embedded']['bitstreams'] as $bitstream)
									{
										if($fileName.'.jpg' == $bitstream['name'] || $fileName.'.txt' == $bitstream['name'])
										{
											$response = dspaceDeleteBitstream($bitstream['uuid']);

											//print_r($response);

											if($response['status'] == 'success')
											{
												$message .= '<br> --- Derived file '.$bitstream['name'].' with UUID '.$bitstream['uuid'].' deleted.';
											}
											else
											{
												print_r($response);
											}
										}
									}
								}
								else
								{
									print_r($response);
								}
							}
						}
					}
					else
					{
						print_r($response);
					}
				}
				else
				{
					print_r($response);
				}
			}
			else
			{
				$message .= '-- no bitstream uuid given for deletion';
			}
		}

		//check for existing files
		$bundleUUID = '';

		$response = dspaceListItemBundles($itemID);
		
		if($response['status'] == 'success')
		{
			$bundles = json_decode($response['body'], TRUE);

			foreach($bundles['_embedded']['bundles'] as $bundle)
			{ 
				if($bundle['name'] == 'ORIGINAL')
				{
					$bundleUUID = $bundle['uuid'];

					$message .= '<br>-- Existing Bundle UUID: '.$bundleUUID;

					$response = dspaceListBundlesBitstreams($bundleUUID);

					if($response['status'] == 'success')
					{
						$bitstreams = json_decode($response['body'], TRUE);

						foreach($bitstreams['_embedded']['bitstreams'] as $bitstream)
						{
							$message .= '<br> --- Existing file '.$bitstream['sequenceId'].': <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstream['uuid'].'/content">'.$bitstream['name'].'</a>';

							if($bitstream['sizeBytes'] > 1000000)
							{
								$message .= ' - '.($bitstream['sizeBytes']/1000000).' MB';
							}
							else
							{
								$message .= ' - '.($bitstream['sizeBytes']/1000).' KB';
							}

							//form with button to allow deletion of existing file
							$form = '<form action="reviewCenter.php?formType=uploadFile" method="post">';

							$form .= '<input type="hidden" name="bitstreamUUID" value="'.$bitstream['uuid'].'">';
							$form .= '<input type="hidden" name="fileName" value="'.$bitstream['name'].'">';

							$form .= '<input type="hidden" name="handle" value="'.$handle.'">';
					
							//if uploading based on DOI or handle, the item may not have an idInIRTS
							if(isset($_POST['idInIRTS']))
							{
								$idInIRTS = $_POST['idInIRTS'];
								$form .= '<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">';
							}
					
							$form .= '<input type="hidden" name="itemID" value="'.$itemID.'">';
							
							//if coming from processing form queue
							if(isset($_POST['selections']))
							{
								$form .= '<input type="hidden" name="selections" value="'.$_POST['selections'].'">';
							}

							//include the item in the form
							$form .= '<input type="hidden" name="itemJSON" value="'.htmlspecialchars(json_encode($item)).'">';

							$form .= '<input type="hidden" name="action" value="deleteFile">';

							$form .= '<button class="btn btn-block btn-danger" type="submit" id="submit" name="delete">Delete this file permanently</button>
								</form>';

							$message .= $form;
						}
					}
				}
			}
		}
		
		if(isset($_FILES['files']['tmp_name']))
		{
			$fileUploadStatus = 'success';
			
			$fileUploadReport = '<br>File upload report:';

			//set version variable based on metadata value
			if(isset($item['metadata']['dc.eprint.version'][0]['value']))
			{
				$version = $item['metadata']['dc.eprint.version'][0]['value'];
			}
			elseif(isset($item['metadata']['dc.eprint.version'][0]))
			{
				$version = $item['metadata']['dc.eprint.version'][0];
			}
			else
			{
				$version = '';
			}

			//set type variable based on metadata value
			if(isset($item['metadata']['dc.type'][0]['value']))
			{
				$type = $item['metadata']['dc.type'][0]['value'];
			}
			elseif(isset($item['metadata']['dc.type'][0]))
			{
				$type = $item['metadata']['dc.type'][0];
			}
			else
			{
				$type = '';
			}

			//set embargoEndDate variable based on metadata value
			if(isset($item['metadata']['dc.rights.embargodate'][0]['value']))
			{
				$embargoEndDate = $item['metadata']['dc.rights.embargodate'][0]['value'];
			}
			elseif(isset($item['metadata']['dc.rights.embargodate'][0]))
			{
				$embargoEndDate = $item['metadata']['dc.rights.embargodate'][0];
			}
			else
			{
				$embargoEndDate = '';
			}

			//create new ORIGINAL bundle if needed
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

					$fileUploadReport .= '<br>-- New Bundle Created with UUID: '.$bundleUUID;
				}
				else
				{
					$fileUploadReport .= '<br>-- Failed to create new bundle: '.print_r($response, TRUE);

					$fileUploadStatus = 'failed';
				}
			}
			else
			{
				$fileUploadReport .= '<br>-- Using Existing Bundle UUID: '.$bundleUUID;
			}

			foreach($_FILES['files']['name'] as $key => $fileName)
			{
				$fileUploadReport .= '<br>-- File name: '.$fileName;
				
				$filePath = UPLOAD_FILE_PATH.basename($fileName);
				
				if(move_uploaded_file($_FILES['files']['tmp_name'][$key], $filePath))
				{
					$fileUploadReport .= '<br>--- Uploaded to temporary storage at: '.$filePath;

					if(!empty($bundleUUID))
					{
						if(empty($version))
						{
							$description = $type;
						}
						else
						{
							$description = $version;
						}

						$fileUploadReport .= '<br>--- Description: '.$description;

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

							$fileUploadReport .= '<br> --- Link to new file in repository: <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstreamUUID.'/content">'.$fileName.'</a>';

							// check if the record has an embargo date
							if(!empty($embargoEndDate))
							{
								$response = dspaceResourcePolicies($bitstreamUUID);
								$bitstreamPolicies = json_decode($response['body'], TRUE);
								$bitstreamPolicies = $bitstreamPolicies['_embedded']['resourcepolicies'];

								foreach ($bitstreamPolicies as $bitstreamPolicy)
								{
									$policyID = $bitstreamPolicy['id'];
									$policyPatch[] =  array("op" => "add",
											"path"=> "/startDate",
											"value" => $embargoEndDate);
											
									$policyPatch = json_encode($policyPatch);
									$response = dspaceUpdateResourcePolicy($policyID, $policyPatch);

									if($response['status'] == 'success')
									{
										$fileUploadReport .= '<br> --- With embargo until: '.$embargoEndDate.'<br>';
									}
									else
									{
										$fileUploadReport = '<br> --- Failed to update policy for embargo until: '.$embargoEndDate.': '.print_r($response, TRUE);

										$fileUploadStatus = 'failed';
									}
								}
							}

							if(unlink($filePath))
							{
								$fileUploadReport .= '<br>-- Temporary file removed';
							}
							else
							{
								$fileUploadReport .= '<br>-- Failed to remove temporary file at: '.$filePath;

								$fileUploadStatus = 'failed';
							}
						}
						else
						{
							$fileUploadReport .= '<br> --- Failed to upload file: '.print_r($response, TRUE);

							$fileUploadStatus = 'failed';
						}
					}
				}
				else
				{
					$fileUploadReport .= '<br>- Status: Failed <br>-- File not uploaded to temporary storage';
				}
			}

			//add file upload report to message
			if($fileUploadStatus == 'success')
			{
				$message .= '<div class="alert alert-success">File(s) uploaded successfully: 
					<details>
						<summary>Details</summary>
						<p>'.$fileUploadReport.'</p>
					</details>
				</div>';
			}
			else
			{
				$message .= '<div class="alert alert-danger">Error uploading file(s): 
					<details>
						<summary>Details</summary>
						<p>'.$fileUploadReport.'</p>
					</details>
				</div>';
			}

			//option to return to same processing form for next item
			if(isset($_POST['selections']))
			{
				$message .= '<form method="post" action="reviewCenter.php?'.$_POST['selections'].'">
					<input class="btn btn-lg btn-primary" type="submit" name="next" value="-- Start Next Item --"></input>
					</form>
				</div>';
			}
		}
		else
		{
			$message .= '<hr><b>Upload files for the above item:</b><br>';

			$form = '<form action="reviewCenter.php?formType=uploadFile" method="post" name="fileUpload" id="fileUpload" enctype="multipart/form-data" >';

			$form .= '<input type="hidden" name="handle" value="'.$handle.'">';
	
			//if uploading based on DOI or handle, the item may not have an idInIRTS
			if(isset($_POST['idInIRTS']))
			{
				$idInIRTS = $_POST['idInIRTS'];
				$form .= '<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">';
			}
	
			$form .= '<input type="hidden" name="itemID" value="'.$itemID.'">';
			
			//if coming from processing form queue
			if(isset($_POST['selections']))
			{
				$form .= '<input type="hidden" name="selections" value="'.$_POST['selections'].'">';
			}

			//include the item in the form
			$form .= '<input type="hidden" name="itemJSON" value="'.htmlspecialchars(json_encode($item)).'">';

			$form .= '<input type="file" name="files[]" id="files" required multiple>';

			$form .= '<input type="hidden" name="action" value="uploadFile">';

			$form .= '<button type="submit" id="submit" name="upload" class="btn btn-info">Upload Files to DSpace</button>
				</form><hr>';

			$message .= $form.'	
				</div>';
		}
	}

	$message .= '</div>';

	echo $message;
