<?php
	//check for files
	$bundleUUIDs = [];

	$bundleFiles = [];

	$allExtensionRequestEmails = [];

	$receivedEmail = '';

	$bundles = $item['_embedded']['bundles']['_embedded']['bundles'];

	foreach($bundles as $bundle)
	{
		$bundleUUIDs[$bundle['name']] = $bundle['uuid'];
		
		$bitstreams = $bundle['_embedded']['bitstreams']['_embedded']['bitstreams'];

		$bundleFiles[$bundle['name']] = $bitstreams;

		//received email
		if($bundle['name'] == 'ADMIN')
		{
			foreach($bitstreams as $key => $bitstream)
			{
				if(strpos($bitstream['name'], 'Embargo_Extension_Request_Email') !== FALSE)
				{
					//get email date from bitstream name
					$datePart = str_replace('Embargo_Extension_Request_Email-', '', $bitstream['name']);
					$datePart = str_replace('.html', '', $datePart);
					
					//last 10 characters are date
					$emailDate = substr($datePart, -10);
					
					//add all extension request emails to an array
					$allExtensionRequestEmails[$emailDate] = $bitstream;
				}
			}
		}
	}

	//get the most recent extension request email
	if(!empty($allExtensionRequestEmails))
	{
		//sort by key (date) in descending order
		krsort($allExtensionRequestEmails);

		$mostRecentEmail = reset($allExtensionRequestEmails);

		$response = dspaceGetBitstreamsContent($mostRecentEmail['uuid']);
					
		if($response['status'] == 'success')
		{
			$receivedEmail = $response['body'];

			//replace line breaks with <br>
			$receivedEmail = nl2br($receivedEmail, FALSE);

			$emailDetails = [];
			
			$emailDetails['senderEmail'] = trim(explode('<br>', explode('From:', $receivedEmail)[1])[0]);

			if(strpos($emailDetails['senderEmail'], '.') !== FALSE)
			{
				$emailDetails['senderName'] = ucfirst(trim(explode('.', $emailDetails['senderEmail'])[0]));
			}
			else
			{
				$emailDetails['senderName'] = '';
			}

			$emailDetails['toEmails'] = trim(explode('<br>', explode('To:', $receivedEmail)[1])[0]);

			$emailDetails['dateReceived'] = trim(explode('<br>', explode('Received:', $receivedEmail)[1])[0]);

			$emailDetails['ccEmails'] = trim(explode('<br>', explode('Cc:', $receivedEmail)[1])[0]);

			$emailDetails['emailSubject'] = trim(explode('<br>', explode('Subject:', $receivedEmail)[1])[0]);
		}

		//create variables for each key
		extract($emailDetails);
	}
?>