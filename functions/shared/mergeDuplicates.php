<?php
/*

**** This function updates a duplicate record as merged with a main record 

** Parameters :
	$mainRecordHandle: Handle of the main record.
	$duplicateHandle: Handle of the duplicate record.
	$mainUUID: UUID of the main record.
	$duplicateUUID: UUID of the duplicate record.
	$provenanceStatement: Provenance statement to be added to the metadata of the duplicate record.

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------


function mergeDuplicates($mainRecordHandle, $duplicateHandle, $mainUUID, $duplicateUUID, $provenanceStatement)
{
	global $irts;

	$message = '';

	$status = 'success';

	//update the metadata to point to the main record
	$patches = [];

	//remove old display details
	$patches[] = array(
		"op" => "remove",
		"path" => "/metadata/display.details.left"
	);

	$patches[] = array(
		"op" => "remove",
		"path" => "/metadata/display.details.right"
	);

	//Add new display with link to main record
	$patches[] = array(
		"op" => "add",
		"path" => "/metadata/display.details.right/-",
		"value" => array("value" => '<p>This record has been merged with an existing record at: <a href="http://hdl.handle.net/'.$mainRecordHandle.'">http://hdl.handle.net/'.$mainRecordHandle.'</a>.</p>')
	);

	//Add provenance statement
	$patches[] = array(
		"op" => "add",
		"path" => "/metadata/dc.description.provenance/-",
		"value" => array("value" => $provenanceStatement)
	);

	//mark record as nondiscoverable
	$patches[] = array(
		"op" => "replace",
		"path" => "/discoverable",
		"value" => "false"
	);

	$patchesJSON = json_encode($patches);

	$response = dspacePatchMetadata('items', $duplicateUUID, $patchesJSON);

	if($response['status'] == 'success')
	{
		$message .= '<div class="col-sm-12 alert-success border border-dark rounded">Metadata Patched: '.$duplicateUUID.'</div>';
		
		//get the record of the item to save in the local database
		$response = dspaceGetItem($duplicateUUID);

		if($response['status'] == 'success')
		{
			//save the sourceData in the database
			$result = saveSourceData($irts, 'dspace', $duplicateUUID, $response['body'], 'JSON');

			$recordType = $result['recordType'];

			//process the record
			$result = processDspaceRecord($response['body']);

			$record = $result['record'];

			//save it in the database
			$result = saveValues('dspace', $duplicateUUID, $record, NULL);

			$discoverable = $record['dspace.discoverable'][0]['value'];

			//confirm that duplicate is now nondiscoverable
			if($discoverable == 'FALSE')
			{
				//record is now undiscoverable - handle record will be marked deleted in the database
				update($irts, 'sourceData', array('deleted'), array(date("Y-m-d H:i:s"), $duplicateHandle), 'idInSource', ' AND deleted IS NULL');

				update($irts, 'metadata', array('deleted'), array(date("Y-m-d H:i:s"), $duplicateHandle), 'idInSource', ' AND deleted IS NULL');

				$message .= 'Item Marked as Undiscoverable: '.$duplicateUUID;
			}
			else
			{
				$status = 'error';
				
				$message .= 'Error marking item as undiscoverable: '.$duplicateUUID;
			}
		}
		else
		{
			$status = 'error';
			
			$message .= 'Error getting metadata for item: '.$duplicateUUID.' -- Get Item Failure Response: '.print_r($response, TRUE);
		}
	}
	else
	{
		$status = 'error';
		
		$message .= 'Error patching metadata for item: '.$duplicateUUID.' -- Patch Metadata Failure Response: '.print_r($response, TRUE);
	}

	return array('status'=>$status, 'message' => $message);
}
