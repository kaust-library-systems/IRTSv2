<?php
	//html to display
	$message = '<div class="container">';

	//labels for (some) metadata fields
	$template = [
		'dc.rights.uri' => ['label' => 'License'],
		'dc.rights.embargodate' => ['label' => 'Embargo End Date'],
		'dc.type' => ['label' => 'Type'],
		'dc.contributor.author' => ['label' => 'Authors'],
		'dc.contributor.advisor' => ['label' => 'Advisors'],
		'dc.contributor.committeemember' => ['label' => 'Committee Members'],
		'thesis.degree.discipline' => ['label' => 'Program'],
		'dc.contributor.department' => ['label' => 'KAUST Department'],
		'dc.date.issued' => ['label' => 'Date'],
		'dc.rights.accessrights' => ['label' => 'Access Restrictions'],
		'dc.description.abstract' => ['label' => 'Abstract']
	];

	//echo $action;

	if(!isset($action) || $action === 'skip') //check for workflowitems in DSpace that require review
	{
		$itemID = '';
		
		//$message .= $page;

		//get 1 item at a time
		$response = dspaceSearch('configuration=workflow&scope='.ETD_COMMUNITY_UUID.'&page='.$page.'&size=1');

		if($response['status'] == 'success')
		{
			//prepare empty variable
			$poolTaskID = '';
			
			$results = json_decode($response['body'], TRUE);
	
			foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
			{
				$poolTask = $result['_embedded']['indexableObject'];

				$poolTaskID = $poolTask['id'];
			}

			if(empty($poolTaskID))
			{
				$message .= 'No thesis or dissertation submissions currently require review.';
			}
			else
			{
				include 'snippets/forThesisApproval/displayItemInfo.php';
			}
		}
		else
		{
			$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Search Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details></div>';
		}
	}
	else //action taken from form
	{
		//if an action step fails, flag will be FALSE and further steps will be skipped and email will not be sent
		$proceed = TRUE;

		//claim and approve the submission, then update the metadata and bitstream information, including setting the embargo
		if($action == 'approve')
		{
			include 'snippets/forThesisApproval/approveSubmission.php';
		}
		elseif($action == 'editMetadata') //update the metadata
		{
			include 'snippets/forThesisApproval/editMetadata.php';
		}
		elseif($action == 'updateMetadata') //update the metadata
		{
			include 'snippets/forThesisApproval/updateMetadata.php';
		}
		elseif($action == 'identifyAdminFiles') //confirm admin files
		{
			include 'snippets/forThesisApproval/identifyAdminFiles.php';
		}
		elseif($action == 'moveAdminFiles') //move admin files to ADMIN bundle
		{
			include 'snippets/forThesisApproval/moveAdminFilesToCorrectBundle.php';
		}
		elseif($action == 'setEmbargo') //set embargo on remaining files in ORIGINAL bundle
		{
			include 'snippets/forThesisApproval/setEmbargo.php';
		}
		elseif($action == 'editRecipients') //edit email recipients
		{
			include 'snippets/forThesisApproval/editRecipients.php';
		}
		elseif($action == 'sendNotifications')
		{
			include 'snippets/forThesisApproval/sendNotifications.php';
		}
	}

	$message .= '</div>';

	echo $message;
