<?php
	$message .= 'Item UUID: '.$itemUUID.'<br>';

	$response = dspaceGetItem($itemUUID);

	if($response['status'] == 'success')
	{
		$item = json_decode($response['body'], TRUE);

		$handle = $item['handle'];

		$message .= 'Item Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';

		$metadata = $item['metadata'];

		//metadata to array (without 'value' key)
		$record = dSpaceMetadataToArray($metadata);

		$recipients = identifyEtdNotificationRecipients($record);

		//display form for manual correction of recipient emails
		$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">
			<input type="hidden" name="metadataJSON" value="'.htmlspecialchars(json_encode($metadata)).'">
			<input type="hidden" name="itemUUID" value="'.$itemUUID.'">
			<input type="hidden" name="handle" value="'.$handle.'">';

		if(isset($embargoEndDate))
		{
			$message .= '<input type="hidden" name="embargoEndDate" value="'.$embargoEndDate.'">';
		}

		//show recipients for editing
		foreach($recipients as $key => $value)
		{
			if($key == 'errors')
			{
				$message .= 'Error Identifying Notification Recipients: <details>
					<summary>Details</summary>';

				foreach($value as $error)
				{
					$message .= '<p>- Error: '.$error.'</p>';
				}

				$message .= '</details>';
			}
			else
			{
				$message .= $key.': <textarea name="'.$key.'" rows="1">'.$value.'</textarea><br>';
			}
		}

		$message .= '<button class="btn btn-block btn-primary" type="submit" name="action" value="sendNotifications">-- Send Notifications --</button>
		</form>';
	}
	else
	{
		$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Error Retrieving Item: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></div>';

		$proceed = FALSE;
	}
?>