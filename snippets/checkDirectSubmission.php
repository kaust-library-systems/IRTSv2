<?php
	//html to display
	$message = '<div class="container">';

	//labels for (some) metadata fields
	$template = [
		'dc.type' => ['label' => 'Type'],
		'dc.contributor.author' => ['label' => 'Authors'],
		'dc.contributor.department' => ['label' => 'KAUST Department'],
		'dc.date.issued' => ['label' => 'Date']
	];

	if(!isset($action) || $action === 'skip') //check for workflowitems in DSpace that require review
	{
		$itemID = '';
		
		//$message .= $page;

		//get 1 item at a time
		$response = dspaceSearch('configuration=workflow&scope='.RESEARCH_COMMUNITY_UUID.'&page='.$page.'&size=1');

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
				$message .= 'No submissions currently require review.';
			}
			else
			{
				include 'snippets/forDirectSubmissionApproval/displayItemInfo.php';
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
			include 'snippets/forDirectSubmissionApproval/approveSubmission.php';
		}
	}

	$message .= '</div>';

	echo $message;
