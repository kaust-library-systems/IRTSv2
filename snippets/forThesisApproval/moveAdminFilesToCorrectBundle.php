<?php
	$message .= 'Item UUID: '.$itemUUID.'<br>';

	$message .= 'Item Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';

	if(isset($embargoEndDate))
	{
		$message .= 'Embargo End Date: '.$embargoEndDate.'<br>';
	}

	//Create ADMIN bundle if it does not exist
	if(empty($adminBundleUUID))
	{
		$bundle = '{"name":"ADMIN"}';

		$response = dspaceCreateItemBundle($itemUUID, $bundle);

		if($response['status'] == 'success')
		{
			$adminBundleUUID = json_decode($response['body'], TRUE)['uuid'];
		}
		else
		{
			$message .= 'Error Creating ADMIN Bundle: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';
		}
	}

	if(!empty($adminBundleUUID))
	{
		//Move admin files to ADMIN bundle
		foreach($adminFileUUIDs as $adminFileUUID)
		{
			$response = dspaceMoveBitstream($adminFileUUID, $adminBundleUUID);

			if($response['status'] == 'success')
			{
				$message .= '- File '.$adminFileUUID.' moved to ADMIN bundle<br>';

				$message .= 'Success: <details>
					<summary>Details</summary>
					<p>'.print_r($response, TRUE).'</p>
				</details>';
			}
			else
			{
				$message .= 'Error: <details>
					<summary>Details</summary>
					<p>'.print_r($response, TRUE).'</p>
				</details>';
			}
		}
	}
	
	// display button to move to next step
	$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

	$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
	$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
	$message .= '<input type="hidden" name="handle" value="'.$handle.'">';

	if(!empty($embargoEndDate))
	{
		$message .= '<input type="hidden" name="embargoEndDate" value="'.$embargoEndDate.'">';

		$message .= '<input type="hidden" name="originalBundleUUID" value="'.$originalBundleUUID.'">';

		$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="setEmbargo">-- Embargo Requested -> Next Step: Set Embargo --</button>';
	}
	else
	{
		$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="editRecipients">-- No Embargo Requested -> Next Step: Identify Email Recipients --</button>';
	}	
	
	$message .= '</form>';
?>