<?php
	$message .= 'Item UUID: '.$itemUUID.'<br>';

	$message .= 'Item Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';
	
	$response = dspaceListItemBundles($itemUUID);

	if($response['status'] == 'success')
	{
		$bundles = json_decode($response['body'], TRUE);

		$bundles = $bundles['_embedded']['bundles'];

		$bundleID = '';

		foreach($bundles as $bundle)
		{
			if($bundle['name'] == 'ORIGINAL')
			{
				$originalBundleUUID = $bundle['uuid'];

				$response = dspaceListBundlesBitstreams($originalBundleUUID);

				if($response['status'] == 'success')
				{
					$originalBundle = json_decode($response['body'], TRUE);

					$originalBundleBitstreams = $originalBundle['_embedded']['bitstreams'];
				}
				else
				{
					$message .= 'Error Retrieving ORIGINAL Bundle Bitstreams: <details>
						<summary>Details</summary>
						<p>'.print_r($response, TRUE).'</p>
					</details>';
				}
			}
			elseif($bundle['name'] == 'ADMIN')
			{
				$adminBundleUUID = $bundle['uuid'];
			}
		}

		// display files
		$message .= '<br><h2>Uploaded Files</h2>';

		// display metadata fields and values in table
		$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

		$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
		$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
		$message .= '<input type="hidden" name="handle" value="'.$handle.'">';
		
		$message .= '<input type="hidden" name="originalBundleUUID" value="'.$originalBundleUUID.'">';

		if(!empty($adminBundleUUID))
		{
			$message .= '<input type="hidden" name="adminBundleUUID" value="'.$adminBundleUUID.'">';
		}

		if(!empty($embargoEndDate))
		{
			$message .= '<input type="hidden" name="embargoEndDate" value="'.$embargoEndDate.'">';
		}

		//List files in table including file name, size, and download link
		$message .= '<table style="border-collapse: collapse; border: 1px solid black;">
								<tr>
									<th style="border: 1px solid black;">File Name</th>
									<th style="border: 1px solid black;">Size</th>
									<th style="border: 1px solid black;">Download</th>
									<th style="border: 1px solid black;">Administrative Form</th>
								</tr>';

		foreach($originalBundleBitstreams as $file)
		{
			$fileName = $file['metadata']['dc.title'][0]['value'];
			$fileSize = $file['sizeBytes'];
			$fileUUID = $file['uuid'];
			$fileURL = $file['_links']['content']['href'];

			$isAdminForm = FALSE;
			$stringsToMatch = ['advisor', 'approval', 'results', 'form'];
			foreach($stringsToMatch AS $string)
			{
				if(stripos($fileName, $string) !== FALSE)
				{
					$isAdminForm = TRUE;
					break;
				}
			}

			$message .= '<tr>
							<td style="border: 1px solid black;">'.$fileName.'</td>
							<td style="border: 1px solid black;">'.$fileSize.'</td>
							<td style="border: 1px solid black;"><a href="'.str_replace('/server/api/core/', '/', str_replace('/content', '/download', $fileURL)).'">Download</a></td>
							<td style="border: 1px solid black;"><input type="checkbox" name="adminFileUUIDs[]" value="'.$fileUUID.'" '.($isAdminForm ? 'checked' : '').'></td>
						</tr>';
		}

		$message .= '</table>';

		$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="moveAdminFiles">-- Move Admin Forms to ADMIN Bundle --</button>';
		$message .= '</form>';
	}
	else
	{
		$message .= 'Error Listing Bundles: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details>';
	}
?>