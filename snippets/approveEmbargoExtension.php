<?php
	//html to display
	$message = '<div class="container">';

	if(!isset($_POST['action'])) //check for flagged items in DSpace
	{
		$itemID = '';
		
		//get 1 item at a time and include its bundles and bitstreams
		$response = dspaceSearch('query=kaust.embargo.extensionRequested:*&size=1&embed=bundles/bitstreams');

		if($response['status'] == 'success')
		{
			$results = json_decode($response['body'], TRUE);
	
			foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
			{
				$item = $result['_embedded']['indexableObject'];

				$itemID = $item['id'];
			}

			if(empty($itemID))
			{
				$message .= 'No embargo extension requests currently require review.';
			}
			else
			{
				include 'snippets/forEmbargoExtension/displayItemInfo.php';

				include 'snippets/forEmbargoExtension/getBundleFileAndEmailInfo.php';

				//print_r($bundleFiles);
	
				include 'snippets/forEmbargoExtension/displayInstructions.php';
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
		$itemMetadata = json_decode($itemMetadataJSON, TRUE);
		$emailDetails = json_decode($emailDetailsJSON, TRUE);

		//create variables for each key
		extract($emailDetails);

		if($_POST['action'] == 'approveExtension') //Move files from TEMP to ORIGINAL bundle
		{
			include 'snippets/forEmbargoExtension/extendEmbargo.php';
		}

		if($proceed)
		{	
			include 'snippets/forEmbargoExtension/sendEmailAndMarkComplete.php';
		}
		else
		{
			$message .= '<br> -- ERROR - Action steps incomplete - No email sent!';
		}
	}

	$message .= '</div>';

	echo $message;
