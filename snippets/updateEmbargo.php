<?php
	//html to display
	$message = '<div class="container">';

	//check if form has been submitted, otherwise display initial form
	if(isset($_POST['updateEmbargo']))
	{
		//if action is "checkRecord", display details of the item, existing embargo, and the bitstreams for confirmation
		if($_POST['action'] == 'checkRecord')
		{
			//handle form submission
			$handle = trim($_POST['handle']);
			$newEmbargoEndDate = trim($_POST['newEmbargoEndDate']);

			//validate handle (regex for handle format)
			if(!preg_match('/^\d{5}\/\d{6}$/', $handle))
			{
				$message .= '<div class="alert alert-danger">"'.$handle.'" is not a valid handle! Please enter a valid handle.</div>';
			}
			else
			{
				//validate embargo end date (regex for date format)
				if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newEmbargoEndDate))
				{
					$message .= '<div class="alert alert-danger">"'.$newEmbargoEndDate.'" is not a valid date! Please enter a valid date in YYYY-MM-DD format.</div>';
				}
				else
				{
					//search for item by handle and include its bundles and bitstreams in the response
					$response = dspaceSearch('query=dc.identifier.uri:'.$handle.'&size=1&embed=bundles/bitstreams');

					if($response['status'] == 'success')
					{
						$results = json_decode($response['body'], TRUE);
				
						foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
						{
							$item = $result['_embedded']['indexableObject'];

							$itemID = $item['id'];

							$bitstreamUUIDs = [];

							//add repository links to message
							$message .= displayRepositoryLinks($itemID, $handle);

							//add item info to message
							$message .= displayItemInfo($item['metadata']);

							//get the bundles and bitstreams for the item
							$bundles = $item['_embedded']['bundles']['_embedded']['bundles'];

							//loop through the bundles
							foreach($bundles as $bundle)
							{
								//check if bundle name is ORIGINAL, TEXT, or THUMBNAIL (ADMIN bundle files have no permissions and need no embargo)
								if(in_array($bundle['name'], ['ORIGINAL', 'TEXT', 'THUMBNAIL']))
								{
									//display bundle name
									$message .= '<br>Bundle: '.$bundle['name'];
									
									$bitstreams = $bundle['_embedded']['bitstreams']['_embedded']['bitstreams'];

									//loop through the bitstreams
									foreach($bitstreams as $bitstream)
									{
										//get the bitstream UUID
										$bitstreamUUID = $bitstream['uuid'];

										$bitstreamUUIDs[] = $bitstreamUUID;

										//display bitstream details
										$message .= '<br> --- File '.$bitstream['sequenceId'].': <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstream['uuid'].'/content">'.$bitstream['name'].'</a>';

										if($bitstream['sizeBytes'] > 1000000)
										{
											$message .= ' - '.($bitstream['sizeBytes']/1000000).' MB';
										}
										else
										{
											$message .= ' - '.($bitstream['sizeBytes']/1000).' KB';
										}
									}
								}
							}

							//display new embargo end date and type for confirmation
							$message .= '<div class="alert alert-warning">
								<br>
								New embargo end date: '.$newEmbargoEndDate.'
								<br>
								Embargo type: '.$_POST['embargoType'].'
								<br>
								</div>';

							//display confirmation button with related metadata as hidden inputs
							$message .= '<form method="post" action="reviewCenter.php?formType=updateEmbargo">
								<input type="hidden" name="action" value="confirm"></input>
								<input type="hidden" name="itemID" value="'.$itemID.'"></input>
								<input type="hidden" name="handle" value="'.$handle.'"></input>
								<input type="hidden" name="bitstreamUUIDs" value="'.htmlspecialchars(json_encode($bitstreamUUIDs)).'"></input>
								<input type="hidden" name="newEmbargoEndDate" value="'.$newEmbargoEndDate.'"></input>
								<input type="hidden" name="embargoType" value="'.$_POST['embargoType'].'"></input>
								<input class="btn btn-primary" type="submit" name="updateEmbargo" value="Confirm Update"></input>
							</form>';
						}
					}
					else
					{
						$message .= '<div class="alert alert-danger">Search Error: <details>
							<summary>Details</summary>
							<p>'.print_r($response, TRUE).'</p>
						</details></div>';
					}
				}
			}
		}

		//if action is "confirm", update the embargo end date for the item and its bitstreams
		if($_POST['action'] == 'confirm')
		{
			//proceed flag
			$proceed = TRUE;
			
			//get the item ID, handle, and bitstream UUIDs from the form
			$itemID = trim($_POST['itemID']);
			$handle = trim($_POST['handle']);
			$bitstreamUUIDs = json_decode($_POST['bitstreamUUIDs'], TRUE);
			$newEmbargoEndDate = trim($_POST['newEmbargoEndDate']);
			$embargoType = trim($_POST['embargoType']);

			//display item links
			$message .= displayRepositoryLinks($itemID, $handle);

			//display new embargo end date and type
			$message .= '<br>
				Attempting to set:
				- New embargo end date: '.$newEmbargoEndDate.'
				<br>
				- Embargo type: '.$embargoType.'
				<br>';

			//check if metadata should also be embargoed
			if($embargoType == 'filesAndMetadata')
			{
				//update the embargo end date for the item
				$response = dspacePrepareAndApplyPatchesToPolicies($itemID, $newEmbargoEndDate);

				if($response['status'] == 'success')
				{
					$message .= '<div class="alert alert-success"><br>-- Embargo end date for item metadata successfully updated to '.$newEmbargoEndDate.'.
						<details>
							<summary>Details</summary>
							<p>'.$response['report'].'</p>
						</details></div>';
				}
				else
				{
					$message .= '<div class="alert alert-danger">Update Error: <details>
						<summary>Details</summary>
						<p>'.print_r($response, TRUE).'</p>
					</details></div>';

					$proceed = FALSE;
				}
			}

			//extend embargo for bitstreams
			foreach($bitstreamUUIDs as $bitstreamUUID)
			{
				$response = dspacePrepareAndApplyPatchesToPolicies($bitstreamUUID, $newEmbargoEndDate);

				if($response['status'] == 'success')
				{
					$message .= '<div class="alert alert-success"><br>-- Embargo end date for bitstream '.$bitstreamUUID.' successfully updated to '.$newEmbargoEndDate.'.
						<details>
							<summary>Details</summary>
							<p>'.$response['report'].'</p>
						</details></div>';
				}
				else
				{
					$message .= '<div class="alert alert-danger">Update Error: <details>
						<summary>Details</summary>
						<p>'.print_r($response, TRUE).'</p>
					</details></div>';
					
					$proceed = FALSE;
				}
			}

			//if no errors were encountered, proceed with updating metadata
			if($proceed)
			{
				//update embargo date
				$newMetadata['dc.rights.embargodate'][0] = $newEmbargoEndDate;

				//update access rights
				$newMetadata['dc.rights.accessrights'][0] = 'Access is temporarily restricted. The file will become available to the public after the expiration of the embargo on '.$newEmbargoEndDate.'.';	

				//prepare and apply metadata patches
				$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, $_SESSION['displayname']);

				if(in_array($response['status'], array('skipped','failed')))
				{
					$message .= '<div class="alert alert-danger"><br> -- Metadata patch failed: <br> -- Response received was: '.print_r($response, TRUE).'<br></div>'.PHP_EOL;
				}
				else
				{
					$message .= '<div class="alert alert-success"><br> -- Metadata patched successfully.</div>'.PHP_EOL;
				}
			}
		}
	}
	else //display form
	{
		//instructions
		$message .= '<h3>Update Embargo End Date</h3>';
		$message .= '<p>Use this form to update the embargo end date for a record. Enter the record handle and the new embargo end date.</p>';
		
		//record handle field
		$message .= '<form method="post" action="reviewCenter.php?formType=updateEmbargo">
			<div class="form-group">
			  <label for="handle">Handle:</label>
			  <input type="text" class="form-control" id="handle" name="handle" placeholder="10754/xxxxxx">
			</div>';

		//embargo end date
		$message .= '<div class="form-group">
			<label for="newEmbargoEndDate">New embargo end date (YYYY-MM-DD):</label>
			<input type="text" id="newEmbargoEndDate" name="newEmbargoEndDate" placeholder="YYYY-MM-DD">
			</div>';

		//embargo type selection
		$message .= '<div class="form-group
			<label for="embargoType">Embargo type:</label>
			<select id="embargoType" name="embargoType">
				<option value="filesOnly">Embargo Files</option>
				<option value="filesAndMetadata">Embargo Files and Metadata</option>
			</select>
			</div>';

		//hidden fields
		$message .= '<input type="hidden" name="action" value="checkRecord"></input>';
		
		//submit button
		$message .= '<input class="btn btn-primary" type="submit" name="updateEmbargo" value="Check Record"></input>
		</form>';
	}
	
	$message .= '</div>';

	echo $message;
?>