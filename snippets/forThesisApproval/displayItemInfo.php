<?php
	$message .= 'Pool Task ID: '.$poolTaskID.'<br>';

	$workflowItem = $poolTask['_embedded']['workflowitem'];

	$workflowItemID = $workflowItem['id'];
	
	$workflowItemResponse = dspaceGetWorkflowItem($workflowItemID);
	
	if($workflowItemResponse['status'] == 'success')
	{
		$workflowItemBody = json_decode($workflowItemResponse['body'], TRUE);
	}
	else
	{
		$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Error retrieving workflow item details: <details>
			<summary>Details</summary>
			<p>'.print_r($workflowItemResponse, TRUE).'</p>
		</details></div>';
		return;
	}

	$message .= 'Workflow Item ID: '.$workflowItemID.'<br>';

	//$metadata = $workflowItem['sections']['theses_and_dissertationspage2'];
	$metadata = $workflowItemBody['metadata'];
	$itemUUID = $workflowItemBody['uuid'];
	
	$message .= '<h2>Submitted Thesis/Dissertation Metadata</h2>';

	// display metadata fields and values in table
	$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

	$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
	$message .= '<input type="hidden" name="poolTaskID" value="'.$poolTaskID.'">';
	$message .= '<input type="hidden" name="workflowItemID" value="'.$workflowItemID.'">';
	$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
	$message .= '<table style="border-collapse: collapse; border: 1px solid black;">
							<tr>
								<th style="border: 1px solid black;">Field</th>
								<th style="border: 1px solid black; width: 100%">Values</th>
							</tr>';

	foreach($metadata as $field => $values)
	{
		$label = isset($template[$field]['label']) ? $template[$field]['label'] : $field;

		$message .= '<tr>
						<td style="border: 1px solid black;">'.$label.'</td>
						<td style="width: 100%">
						<table style="width: 100%">
							<tr>
							<th style="border: 1px solid black; width: 100%">Value</th>
							</tr>';

		foreach($values as $key => $value)
		{
			$message .= '<tr>
							<td style="border: 1px solid black; width: 100%">'.$value['value'].'</td>
						</tr>';
		}

		$message .= '</table></td></tr>';
	}

	$message .= '</table>';

	// display files
	$message .= '<br><h2>Uploaded Files</h2>';
	$files = $workflowItem['sections']['tusupload']['files'];

	//List files in table including file name, size, and download link
	$message .= '<table style="border-collapse: collapse; border: 1px solid black;">
							<tr>
								<th style="border: 1px solid black;">File Name</th>
								<th style="border: 1px solid black;">Size</th>
								<th style="border: 1px solid black;">Download</th>
							</tr>';

	foreach($files as $file)
	{
		$fileName = $file['metadata']['dc.title'][0]['value'];
		$fileSize = $file['sizeBytes'];
		$fileURL = $file['url'];
		$fileUUID = $file['uuid'];

		$message .= '<tr>
						<td style="border: 1px solid black;">'.$fileName.'</td>
						<td style="border: 1px solid black;">'.$fileSize.'</td>
						<td style="border: 1px solid black;"><a href="'.str_replace('/server/api/core/', '/', str_replace('/content', '/download', $fileURL)).'">Download</a></td>
					</tr>';
	}

	$message .= '</table>';

	$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="approve">-- Approve Submission: Submitter Will Receive Initial Email --</button>
				<br><button class="btn btn-block btn-primary" type="submit" name="action" value="skip">-- Skip: Take No Action and Move to Next Submission --</button>';
	$message .= '</form>';
?>