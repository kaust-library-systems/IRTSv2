<?php
	$message .= 'Item UUID: '.$itemUUID.'<br>';

	$message .= 'Item Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';

	//add ORCIDs based on local person matches
	$result = addORCIDs($metadata);

	$metadata = $result['metadata'];

	$message .= $result['report'];

	//add embargo information to metadata
	if(isset($metadata['dc.rights.embargodate'][0]['value']))
	{
		$embargoEndDate = $metadata['dc.rights.embargodate'][0]['value'];

		$metadata['dc.rights.accessrights'][0]['value'] = 'Access to this '.strtolower($metadata['dc.type'][0]['value']).' is temporarily restricted. The file will become available to the public after the expiration of the embargo on '.$embargoEndDate.'.';

		$message .= '- Access Rights added to metadata: '.$metadata['dc.rights.accessrights'][0]['value'].'<br>'.PHP_EOL;
	}
	else
	{
		$embargoEndDate = '';
	}

	//set display fields
	$result = setDisplayFields($metadata);

	$metadata = $result['metadata'];

	$itemJSON = dspacePrepareItem($metadata, $itemUUID);
					
	$response = dspaceUpdateItem($itemUUID, $itemJSON);
	
	if($response['status'] == 'success')
	{
		$message .= '- Item Metadata Updated<br>';

		$displayDetailsLeft = $metadata['display.details.left'][0];

		$displayDetailsRight = $metadata['display.details.right'][0];

		//show display details fields in a table with two columns, left and right
		$message .= '<h2>Updated Thesis/Dissertation Metadata</h2>';

		$message .= '<table style="border-collapse: collapse; border: 1px solid black;">
						<tr>
							<td style="border: 1px solid black; padding: 5px;">'.$displayDetailsLeft.'</td>
							<td style="border: 1px solid black; padding: 5px;">'.$displayDetailsRight.'</td>
						</tr>
					</table>';
	}
	else
	{
		$message .= 'Error Updating Item Metadata: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
			<p>'.print_r($itemJSON).'</p>
		</details>';
	}

	// display button to move to next step
	$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

	$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
	$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
	$message .= '<input type="hidden" name="handle" value="'.$handle.'">';
	$message .= '<input type="hidden" name="embargoEndDate" value="'.$embargoEndDate.'">';
	$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="identifyAdminFiles">-- Next Step: Identify Admin Files --</button>';
	$message .= '</form>';
?>