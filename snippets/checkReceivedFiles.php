<?php
	//html to display
	$message = '<div class="container">';

	if(!isset($_POST['action'])) //check for flagged items in DSpace
	{
		$itemID = '';
		
		//get 1 item at a time and include its bundles and bitstreams
		$response = dspaceSearch('query=kaust.manuscript.received:*&size=1&embed=bundles/bitstreams');

		if($response['status'] == 'success')
		{
			$results = json_decode($response['body'], TRUE);
	
			foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
			{
				$item = $result['_embedded']['indexableObject'];

				$itemID = $item['id'];

				$handle = $item['handle'];
			}

			if(empty($itemID))
			{
				$message .= 'No files currently require review.';
			}
			else
			{
				//add repository links to message
				$message .= displayRepositoryLinks($itemID, $handle);
				
				//add item info to message
				$message .= displayItemInfo($item['metadata']);

				//add rights details to message
				$message .= displayItemInfo($item['metadata'], '', 'rights', FALSE, array('dc.date.issued'));
				
				$receivedEmailID = $item['metadata']['kaust.manuscript.received'][0]['value'];

				//set embargoEndDate variable
				if(isset($item['metadata']['dc.rights.embargodate'][0]['value']))
				{
					$embargoEndDate = $item['metadata']['dc.rights.embargodate'][0]['value'];
				}
				else
				{
					$embargoEndDate = '';
				}

				include 'snippets/forManuscriptReceipt/getBundleFileAndEmailInfo.php';
	
				include 'snippets/forManuscriptReceipt/displayInstructions.php';
			}
		}
		else
		{
			$message .= 'Search Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';
		}
	}
	else //action taken from form
	{
		//if an action step fails, flag will be FALSE and further steps will be skipped and email will not be sent
		$proceed = TRUE;
		
		//display item links
		$message .= displayRepositoryLinks($itemID, $handle);

		$bundleUUIDs = json_decode($bundleUUIDsJSON, TRUE);
		$bundleFiles = json_decode($bundleFilesJSON, TRUE);
		$emailDetails = json_decode($emailDetailsJSON, TRUE);

		//create variables for each key
		extract($emailDetails);

		if($_POST['action'] == 'addReceivedFiles') //Move files from TEMP to ORIGINAL bundle
		{
			include 'snippets/forManuscriptReceipt/removeOldFilesAndAddNewFiles.php';
		}
		elseif(in_array($_POST['action'], ['sendNoteToRepository','sendRequestForCorrectVersion'])) //Remove files from TEMP and ADMIN bundles
		{
			include 'snippets/forManuscriptReceipt/removeTempAndAdminFiles.php';
		}

		if($proceed)
		{	
			include 'snippets/forManuscriptReceipt/sendEmailAndMarkComplete.php';
		}
		else
		{
			$message .= '<br> -- ERROR - Action steps incomplete - No email sent!';
		}
	}

	$message .= '</div>';

	echo $message;
