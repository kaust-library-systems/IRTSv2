<?php
	//check for files
	$bundleUUIDs = [];

	$bundleFiles = [];

	$existingFilesList = '';

	$receivedFilesList = '';

	$receivedEmail = '';

	$bundles = $item['_embedded']['bundles']['_embedded']['bundles'];

	foreach($bundles as $bundle)
	{
		$bundleUUIDs[$bundle['name']] = $bundle['uuid'];
		
		$bitstreams = $bundle['_embedded']['bitstreams']['_embedded']['bitstreams'];

		$bundleFiles[$bundle['name']] = $bitstreams;

		//existing files
		if($bundle['name'] == 'ORIGINAL')
		{
			foreach($bitstreams as $key => $bitstream)
			{
				$existingFilesList .= '<br> --- Existing file '.$key.': <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstream['uuid'].'/content" target="_blank" rel="noopener noreferrer">'.$bitstream['name'].'</a>';

				if($bitstream['sizeBytes'] > 1000000)
				{
					$existingFilesList .= ' - '.($bitstream['sizeBytes']/1000000).' MB';
				}
				else
				{
					$existingFilesList .= ' - '.($bitstream['sizeBytes']/1000).' KB';
				}
			}
		}

		//received files
		if($bundle['name'] == 'TEMP')
		{
			foreach($bitstreams as $key => $bitstream)
			{
				$receivedFilesList .= '<br> --- Received file '.$key.': <a href="'.REPOSITORY_API_URL.'core/bitstreams/'.$bitstream['uuid'].'/content" target="_blank" rel="noopener noreferrer">'.$bitstream['name'].'</a>';

				if($bitstream['sizeBytes'] > 1000000)
				{
					$receivedFilesList .= ' - '.($bitstream['sizeBytes']/1000000).' MB';
				}
				else
				{
					$receivedFilesList .= ' - '.($bitstream['sizeBytes']/1000).' KB';
				}
			}
		}

		//received email
		if($bundle['name'] == 'ADMIN')
		{
			foreach($bitstreams as $key => $bitstream)
			{
				if($bitstream['name'] == 'Accepted_Manuscript_Submission_Email.html')
				{
					$response = dspaceGetBitstreamsContent($bitstream['uuid']);
					
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

					break;
				}
			}
		}
	}
?>