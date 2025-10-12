<?php
	// Always set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

	//CC repository email
	$headers .= "From: " .IR_EMAIL. "\r\n";
	$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";
	
	//get fields from metadata
	$metadata = json_decode($metadataJSON, TRUE);
	$title = $metadata['dc.title'][0]['value'];
	$type = $metadata['dc.type'][0]['value'];
	$dateArchived = explode("T",$metadata['dc.date.accessioned'][0]['value'])[0];
	$studentKaustID = $metadata['dc.person.id'][0]['value'];

	//content of notice to author, advisor and GPC includes embargo date if set
	if(isset($metadata['dc.rights.embargodate'][0]['value']))
	{
		$embargoEndDate = $metadata['dc.rights.embargodate'][0]['value'];

		$archivalNoticeToAuthorAdvisorGPC = '<p>The '.strtolower($type).' "'.$title.'" by '.$studentName. ' has been archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$handle.'">http://hdl.handle.net/'.$handle.'</a>.</p>
				<p>The full text of this '.strtolower($type).' is under restricted access and will become available to the public after the expiration of the embargo on '.$embargoEndDate.'. An extension can be requested at that time if a further embargo is required.</p>
				<p>Thank you for the deposit and for using our services. Please write to us if you have any questions.</p>
				<p>Regards,</p>
				<p>The KAUST Repository Team, University Library</p>';
	}
	else
	{
		$archivalNoticeToAuthorAdvisorGPC = '<p>The '.strtolower($type).' "'.$title.'" by '.$studentName. ' has been archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$handle.'">http://hdl.handle.net/'.$handle.'</a>.</p>
				<p>The full text is available for immediate public access and you can add this permanent URL to your academic profiles and share via the internet.</p>
				<p>Thank you for the deposit and for using our services. Please write to us if you have any questions.</p>
				<p>Regards,</p>
				<p>The KAUST Repository Team, University Library</p>';
	}

	if(IRTS_TEST) //send to IR email if in test mode
	{
		$to = IR_EMAIL;
	}
	else
	{
		$to = $studentEmail.','.$advisorEmail.','.$gpcEmail;
	}

	$subject = $type." archived in the KAUST repository";

	if(mail($to, $subject, $archivalNoticeToAuthorAdvisorGPC, $headers))
	{
		$message .= '- Archival notice sent to author, advisor and GPC ('.$to.')<br>';
	}
	else
	{
		$message .= '- Failed to send archival notice to author, advisor and GPC ('.$to.')<br>';
	}

	//notice to registrar's office
	$archivalNoticeToRegistrar = '<p>The '. strtolower($type).' "'.$title.'" by '.$studentName. ' with KAUST ID '.$studentKaustID.' was archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$handle.'">http://hdl.handle.net/'.$handle.'</a> on '.$dateArchived.'.</p>
						<p>Regards,</p>
						<p>The KAUST Repository Team, University Library</p>';

	$subject = "New ".strtolower($type)." archived in the KAUST Repository";

	if(IRTS_TEST) //send to IR email if in test mode
	{
		$to = IR_EMAIL;
	}
	else
	{
		$to = REGISTRAR_EMAILS;
	}

	if(mail($to, $subject, $archivalNoticeToRegistrar, $headers))
	{
		$message .= '- Archival notice sent to registrar\'s office ('.$to.')<br>';
	}
	else
	{
		$message .= '- Failed to send archival notice to registrar\'s office ('.$to.')<br>';
	}

	// send reference list request to author
	$referenceListRequestToAuthor = '<p>Dear '.$studentName.',</p>
	<p>The University Library is experimenting with methods to create machine-readable links between theses and dissertations and the publications that they cite. We have looked at a number of automated processes to accomplish this based on the reference lists in thesis PDFs. However, the results are often incomplete or inaccurate.</p>
	
	<p>As a result, we are piloting an option for students to provide their own reference list as a Bibtex export from their citation management software (Zotero, EndNote, etc.). If you are interested in adding a machine readable reference list to the record for your '. strtolower($type).' "'.$title.'" in the repository at <a href="http://hdl.handle.net/'.$handle.'">http://hdl.handle.net/'.$handle.'</a>, we invite you to attach the appropriate reference list in reply to this email.</p>
	<p>By providing the reference list you also confirm to us that it is an accurate and complete list of the references included in the final version of your '. strtolower($type).'.
	This is entirely voluntary, and not a requirement related to your degree. This request does not affect any embargo placed on your work.</p>
	
	<p>If you have any questions or concerns, please let us know.</p>
							<p>Regards,</p>
							<p>The KAUST Repository Team, University Library</p>';

	$subject = "Adding ".strtolower($type)." reference list in the KAUST repository";

	if(IRTS_TEST) //send to IR email if in test mode
	{
		$to = IR_EMAIL;
	}
	else
	{
		$to = $studentEmail;
	}

	if(mail($to, $subject, $referenceListRequestToAuthor, $headers))
	{
		$message .= '- Reference list request sent to author ('.$to.')<br>';
	}
	else
	{
		$message .= '- Failed to send reference list request to author ('.$to.')<br>';
	}

	//clear session variables and selections so that the next submission can be processed
	unset($_SESSION['selections']);
	unset($_SESSION['variables']);

	// check for any thesis submissions awaiting approval
	$response = dspaceSearch('configuration=workflow&scope='.ETD_COMMUNITY_UUID.'&size=1');

	//if search successful
	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		$total = $results['_embedded']['searchResult']['page']['totalElements'];

		$message .= '<hr>Thesis and Dissertation Submissions Awaiting Approval: <a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkThesisSubmission">'.$total.' Submissions Remaining</a>';
	}
	else
	{
		echo '<tr><td>Search Error: </td><td><details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}
?>