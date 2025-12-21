<?php
	$inReplyToID = explode('::split::', $receivedEmailID)[0];
	$messageID = explode('::split::', $receivedEmailID)[1];

	//Headers
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= 'From: '.INSTITUTION_ABBREVIATION.' Repository<'.IR_EMAIL.'>' . "\r\n";
	$headers .= 'In-Reply-To: '.$inReplyToID . "\r\n";
	$headers .= 'References: '.$inReplyToID . "\r\n";

	$emailToSend = nl2br($_POST['body'], FALSE)."<br><br>".$receivedEmail;

	//To reply to the repository with a note requesting follow up
	if($_POST['action'] == 'sendNoteToRepository')
	{
		$emailToSend = 'Follow up needed: '.$emailToSend;

		$recipient = IR_EMAIL;
	}
	
	//To reply to the sender after extension completed
	if(in_array($_POST['action'], ['approveExtension']))
	{
		$emailToSend = str_replace('{newEmbargoEndDatePlaceholder}', $newEmbargoEndDate, $emailToSend);
		
		$recipient = $senderEmail;

		$headers .= 'Cc: '.IR_EMAIL;
		
		if(!empty($ccEmails))
		{
			$headers .= ', '.str_replace('"', '', str_replace(';', ', ', $ccEmails));
		}

		if(!empty($toEmails))
		{
			$headers .= ', '.str_replace('"', '', str_replace(';', ', ', $toEmails));
		}

		$headers .= "\r\n";

		//$recipient = IR_EMAIL;
	}
	
	if(mail($recipient, 'RE: '.$emailSubject, $emailToSend, $headers))
	{
		$message .= '<div class="alert alert-success">-- Email sent.</div>';
	
		$patch = array(array("op" => "remove",
		"path" => "/metadata/kaust.embargo.extensionRequested"));

		$patchJSON = json_encode($patch);
			
		$response = dspacePatchMetadata('items', $itemID, $patchJSON);

		if($response['status'] == 'success')
		{
			$message .= '<div class="alert alert-success">-- Item marked complete, metadata flag removed.</div>';

			$response = markEmailAsComplete($messageID);

			if($response['status'] == 'success')
			{
				$message .= '<div class="alert alert-success">-- Email category set to "Complete", email flag set as "Complete".</div>';

				$message .= '<hr>
		<a href="reviewCenter.php?formType=approveEmbargoExtension" type="button" class="btn btn-primary rounded" style="margin-left: 10px;">-- Start Next Item --</a>';
			}
			else
			{
				$message .= 'Power Automate Error: <details>
					<summary>Details</summary>
					<p>'.print_r($response, TRUE).'</p>
				</details>';
			}
		}
		else
		{
			$message .= 'Patch Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';
		}
	}
	else
	{
		$message .= 'Error! -- Email failed to send for item with ID# '.$itemID.'.';
	}
	