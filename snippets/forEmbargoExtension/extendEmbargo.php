<?php
	//Check whether custom date was entered or extension period was selected
	if(!empty($_POST['newEmbargoEndDate']))
	{
		$newEmbargoEndDate = $_POST['newEmbargoEndDate'];
	}
	else
	{
		//extend existing embargo based on selected period
		$newEmbargoEndDate = date_create($embargoEndDate);
		date_modify($newEmbargoEndDate, $selectedExtensionPeriod);
		$newEmbargoEndDate = date_format($newEmbargoEndDate, 'Y-m-d');
	}
	
	$type = $itemMetadata['dc.type'][0]['value'];
	$provenance = 'Embargo extension request received from '.$senderEmail.' at '.$dateReceived.'. '.PHP_EOL.'Approved for extension until '.$newEmbargoEndDate.' by '.$_SESSION['displayname'].' ('.$_SESSION['mail'].') at '.NOW.'. ';

	//For posters, the metadata record is also embargoed
	if($type == 'Poster')
	{
		$response = dspacePrepareAndApplyPatchesToPolicies($itemID, $newEmbargoEndDate);

		if($response['status'] == 'success')
		{
			$message .= '<br>-- Embargo end date for item '.$itemID.' successfully updated to '.$newEmbargoEndDate.'.
				<details>
					<summary>Details</summary>
					<p>'.$response['report'].'</p>
				</details>';
		}
		else
		{
			$message .= 'Update Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';

			$proceed = FALSE;
		}
	}

	//extend embargo for bitstreams
	foreach($bundleFiles as $bundleName => $bitstreams)
	{
		if(in_array($bundleName, ['ORIGINAL', 'THUMBNAIL', 'TEXT']))
		{
			foreach($bitstreams as $bitstream)
			{
				$response = dspacePrepareAndApplyPatchesToPolicies($bitstream['id'], $newEmbargoEndDate);

				if($response['status'] == 'success')
				{
					$message .= '<br>-- Embargo end date for bitstream '.$bitstream['id'].' successfully updated to '.$newEmbargoEndDate.'.
						<details>
							<summary>Details</summary>
							<p>'.$response['report'].'</p>
						</details>';
				}
				else
				{
					$message .= 'Update Error: <details>
						<summary>Details</summary>
						<p>'.print_r($response, TRUE).'</p>
					</details>';
					
					$proceed = FALSE;
				}
			}
		}
	}

	//if no errors were encountered, proceed with updating metadata
	if($proceed)
	{
		//Add provenance statement
		$itemMetadata['dc.description.provenance'][] = array("value" => $provenance);

		//update embargo date
		$itemMetadata['dc.rights.embargodate'][0]['value'] = $newEmbargoEndDate;

		//update access rights
		$itemMetadata['dc.rights.accessrights'][0]['value'] = 'Access to this '.strtolower($itemMetadata['dc.type'][0]['value']).' is temporarily restricted. The file will become available to the public after the expiration of the embargo on '.$newEmbargoEndDate.'.';		

		//set display fields
		$result = setDisplayFields($itemMetadata);

		$itemMetadata = $result['metadata'];

		$itemJSON = dspacePrepareItem($itemMetadata, $itemID);
						
		$response = dspaceUpdateItem($itemID, $itemJSON);
	
		if($response['status'] == 'success')
		{
			$message .= '<br>-- Provenance statement added, embargo updated in metadata and in display.';
		}
		else
		{
			$message .= 'Update Error: <details>
				<summary>Details</summary>
				<p>'.print_r($response, TRUE).'</p>
			</details>';
		}
	}