<?php
	$provenance = 'Files received from '.$senderEmail.' at '.$dateReceived.'. '.PHP_EOL.'Approved for upload by '.$_SESSION['displayname'].' ('.$_SESSION['mail'].') at '.NOW.'. ';

	//remove old files and their derivatives
	foreach($bundleFiles as $bundleName => $bitstreams)
	{
		if(in_array($bundleName, ['ORIGINAL', 'TEXT', 'THUMBNAIL']))
		{
			foreach($bitstreams as $bitstream)
			{
				$response = dspaceDeleteBitstream($bitstream['uuid']);
			
				//print_r($response);

				if($response['status'] == 'success')
				{
					$message .= '<br> --- Existing file with UUID '.$bitstream['uuid'].' deleted.';

					$provenance .= PHP_EOL.'Existing file removed: '.$bitstream['name'].': '.$bitstream['sizeBytes'].' bytes, checksum: '.$bitstream['checkSum']['value'].' (MD5)';
				}
				else
				{
					$message .= print_r($response, TRUE);

					$proceed = FALSE;

					break 2;
				}
			}
		}
	}

	//create ORIGINAL bundle if needed
	if($proceed && !isset($bundleUUIDs['ORIGINAL']))
	{
		$bundle = array("name" => "ORIGINAL");
									
		$bundleJSON = json_encode($bundle);
				
		$response = dspaceCreateItemBundle($itemID, $bundleJSON);

		//print_r($response);

		if($response['status'] == 'success')
		{
			$newBundle = json_decode($response['body'], TRUE);

			$bundleUUIDs['ORIGINAL'] = $newBundle['uuid'];

			$message .= '<br>New Bundle UUID: '.$bundleUUIDs['ORIGINAL'];
		}
		else
		{
			$message .= print_r($response, TRUE);

			$proceed = FALSE;
		}
	}

	//move TEMP files to ORIGINAL bundle
	if($proceed)
	{
		foreach($bundleFiles['TEMP'] as $bitstream)
		{
			$response = dspaceMoveBitstream($bitstream['uuid'], $bundleUUIDs['ORIGINAL']);
		
			//print_r($response);

			if($response['status'] == 'success')
			{
				$message .= '<br> --- TEMP file with UUID '.$bitstream['uuid'].' moved to ORIGINAL bundle.';

				$provenance .= PHP_EOL.'New file added: '.$bitstream['name'].': '.$bitstream['sizeBytes'].' bytes, checksum: '.$bitstream['checkSum']['value'].' (MD5)';

				// check if the record has an embargo date
				if(!empty($embargoEndDate))
				{
					$response = dspaceResourcePolicies($bitstream['uuid']);
					$bitstreamPolicies = json_decode($response['body'], TRUE);
					$bitstreamPolicies = $bitstreamPolicies['_embedded']['resourcepolicies'];

					foreach ($bitstreamPolicies as $bitstreamPolicy)
					{
						$policyPatch = [];
						
						$policyID = $bitstreamPolicy['id'];
						$policyPatch[] =  array("op" => "add",
								"path"=> "/startDate",
								"value" => $embargoEndDate);
								
						$policyPatchJSON = json_encode($policyPatch);
						$response = dspaceUpdateResourcePolicy($policyID, $policyPatchJSON);

						if($response['status'] == 'success')
						{
							$message .= ' -- With embargo until: '.$embargoEndDate.'<br>';
						}
						else
						{
							print_r($response);
						}
					}
				}
			}
			else
			{
				$message .= print_r($response, TRUE);

				$proceed = FALSE;
			}
		}

		if($proceed)
		{
			//remove TEMP files
			$response = dspaceDeleteBundle($bundleUUIDs['TEMP']);
		
			//print_r($response);

			if($response['status'] == 'success')
			{
				$message .= '<br> --- TEMP bundle with UUID '.$bundleUUIDs['TEMP'].' deleted.';
			}
			else
			{
				$message .= print_r($response, TRUE);

				$proceed = FALSE;
			}
		}

		//Add provenance statement
		$provenancePatch[] =  array("op" => "add",
								"path"=> "/metadata/dc.description.provenance/-",
								"value" => $provenance);

		$provenancePatchJSON = json_encode($provenancePatch);

		$response = dspacePatchMetadata('items', $itemID, $provenancePatchJSON);

		if($response['status'] == 'success')
		{
			$message .= '<br>-- Provenance statement added with file and approval information.';
		}
		else
		{
			$message .= 'Patch Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';
		}
	}