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
	
	//To reply to the sender to request a different version of the file
	if(in_array($_POST['action'], ['sendRequestForCorrectVersion','addReceivedFiles']))
	{
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
	}
	
	if(mail($recipient, 'RE: '.$emailSubject, $emailToSend, $headers))
	{
		$message .= '<br>-- Email sent.';
	
		$patch = array(array("op" => "remove",
		"path" => "/metadata/kaust.manuscript.received"));

		$patchJSON = json_encode($patch);
			
		$response = dspacePatchMetadata('items', $itemID, $patchJSON);

		if($response['status'] == 'success')
		{
			$message .= '<br>-- Item marked complete, metadata flag removed.';

			$response = markEmailAsComplete($messageID);

			if($response['status'] == 'success')
			{
				$message .= '<br>-- Email category set to "Complete", email flag set as "Complete".';

				$message .= '<hr>
		<a href="reviewCenter.php?formType=checkReceivedFiles" type="button" class="btn btn-primary rounded" style="margin-left: 10px;">-- Start Next Item --</a>';
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
	