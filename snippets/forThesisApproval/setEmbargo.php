<?php
	//check which files are left in the original bundle after moving out the Admin forms
	$response = dspaceListBundlesBitstreams($originalBundleUUID);

	if($response['status'] == 'success')
	{
		$originalBundle = json_decode($response['body'], TRUE);

		$originalBundleBitstreams = $originalBundle['_embedded']['bitstreams'];

		//prepare embargo patch to set embargo end date as policy start date
		$embargoPatch = array(array(
			"op" => "add",
			"path"=> "/startDate",
			"value" => $embargoEndDate));

		$embargoPatchJSON = json_encode($embargoPatch);

		//get resource policies for each bitstream
		foreach($originalBundleBitstreams as $bitstream)
		{
			$bitstreamUUID = $bitstream['uuid'];

			$response = dspaceResourcePolicies($bitstreamUUID);

			if($response['status'] == 'success')
			{
				$resourcePolicies = json_decode($response['body'], TRUE);

				$resourcePolicies = $resourcePolicies['_embedded']['resourcepolicies'];

				foreach($resourcePolicies as $policy)
				{
					$policyID = $policy['id'];

					$response = dspaceUpdateResourcePolicy($policyID, $embargoPatchJSON);

					if($response['status'] == 'success')
					{
						$message .= 'Embargo added to ORIGINAL Bundle Bitstream Resource Policy for file named: '.$bitstream['name'].PHP_EOL;
					}
					else
					{
						$message .= 'Error Adding Embargo to ORIGINAL Bundle Bitstream Resource Policy for file named: '.$bitstream['name'].'<details>
							<summary>Details</summary>
							<p>'.print_r($response, TRUE).'</p>
						</details>';
					}
				}
			}
			else
			{
				$message .= 'Error Listing ORIGINAL Bundle Bitstream Resource Policies: <details>
					<summary>Details</summary>
					<p>'.print_r($response, TRUE).'</p>
				</details>';
			}
		}
	}
	else
	{
		$message .= 'Error Listing ORIGINAL Bundle Bitstreams: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details>';
	}

	// display button to move to next step
	$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

	$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
	$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
	$message .= '<input type="hidden" name="handle" value="'.$handle.'">';
	$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="editRecipients">-- Next Step: Identify Email Recipients --</button>';
	$message .= '</form>';
?>