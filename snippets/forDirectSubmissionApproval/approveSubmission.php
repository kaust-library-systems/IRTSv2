<?php
	//display steps of approval process
	$message .= 'Pool Task ID: '.$poolTaskID.'<br>';
			
	$response = dspaceClaimPoolTask($poolTaskID);

	if($response['status'] == 'success')
	{
		$message .= '- Pool Task Claimed<br>';

		$claimedTask = json_decode($response['body'], TRUE);

		$claimedTaskID = $claimedTask['id'];

		$message .= 'Claimed Task ID: '.$claimedTaskID.'<br>';

		$response = dspaceApproveClaimedTask($claimedTaskID);

		if($response['status'] == 'success')
		{
			$message .= '- Claimed Task Approved<br>';


			$message .= 'Item UUID: <a href="'.REPOSITORY_URL.'/items/'.$itemUUID.'">'.$itemUUID.'</a><br>';

			// display button to move to next step
			$message .= '<form action="reviewCenter.php?formType=addNewItem" method="post">';

			$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
			$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';

			$message .= '<br><button class="btn btn-block btn-success" type="submit" name="addItem" value="editMetadata">-- Next Step: Edit Approved Metadata --</button>';
			$message .= '</form>';
		}
		else
		{
			$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Error Approving Pool Task: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details></div>';
		}
	}
	else
	{
		$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Error Claiming Pool Task: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></div>';
	}
?>