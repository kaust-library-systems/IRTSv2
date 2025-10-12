<?php
/*

**** This function sets the relationships between the an older and newer version of a record and marks the older version as merged with the newer version

** Parameters :
	$mainRecordHandle: Handle of the main record.
	$duplicateHandle: Handle of the duplicate record.
	$mainUUID: UUID of the main record.
	$duplicateUUID: UUID of the duplicate record.

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function mergeVersions($mainRecordHandle, $duplicateHandle, $mainUUID, $duplicateUUID)
{
	global $irts;

	$message = '';

	$status = 'success';

	//get the main record
	$response = dspaceGetItem($mainUUID);

	if($response['status'] == 'success')
	{
		//get the main record
		$item = json_decode($response['body'], TRUE);

		$record = $item['metadata'];

		//add version relationship to the main record
		$record['dc.relation.isversionof'][] = array('value' => 'Handle:'.$duplicateHandle);
	
		//set display relations field
		$result = setDisplayRelationsField($record);

		$record = $result['record'];

		$result = setDisplayFields($record);

		$record = $result['metadata'];

		//add provenance statement
		$record['dc.description.provenance'][] = array('value' => 'Marked as newer version of '.$duplicateHandle.' at '.date('Y-m-d H:i:s').' by '.$_SESSION['displayname'].' using the '.IR_EMAIL.' user account.');

		$itemJSON = dspacePrepareItem($record, $mainUUID);
			
		$response = dspaceUpdateItem($mainUUID, $itemJSON);

		if($response['status'] == 'success')
		{
			//get the record of the item to save in the local database
			$response = dspaceGetItem($mainUUID);

			if($response['status'] == 'success')
			{
				//save the sourceData in the database
				$result = saveSourceData($irts, 'dspace', $mainUUID, $response['body'], 'JSON');

				$recordType = $result['recordType'];

				//process the record
				$result = processDspaceRecord($response['body']);

				$record = $result['record'];

				//save it in the database
				$result = saveValues('dspace', $mainUUID, $record, NULL);

				$existingFieldsToIgnore = [
					'dspace.date.modified',
					'dspace.community.handle',
					'dspace.collection.handle',
					'dspace.bundle.name',
					'dspace.record.visibility'
				];
				
				$result = saveValues('repository', $mainRecordHandle, $record, NULL, $existingFieldsToIgnore);

				//update the duplicate record to point to the main record
				
				//get the duplicate record
				$response = dspaceGetItem($duplicateUUID);

				if($response['status'] == 'success')
				{
					$item = json_decode($response['body'], TRUE);

					$record = $item['metadata'];

					//add version relationship to the duplicate record
					$record['dc.relation.hasversion'][] = array('value' => 'Handle:'.$mainRecordHandle);

					//set display relations field
					$result = setDisplayRelationsField($record);
					
					$record = $result['record'];

					$result = setDisplayFields($record);

					$record = $result['metadata'];

					//add merger information at top of right display field
					$record['display.details.right'][0] = '<p>This record has been merged with an existing record at: <a href="http://hdl.handle.net/'.$mainRecordHandle.'">http://hdl.handle.net/'.$mainRecordHandle.'</a>.</p>'.$record['display.details.right'][0];

					//add provenance statement
					$record['dc.description.provenance'][] = array('value' => 'Marked as previous version of and merged with '.$mainRecordHandle.' at '.date('Y-m-d H:i:s').' by '.$_SESSION['displayname'].' using the '.IR_EMAIL.' user account.');

					//mark item as nondiscoverable (discoverable = false)
					$itemJSON = dspacePrepareItem($record, $duplicateUUID);

					$response = dspaceUpdateItem($duplicateUUID, $itemJSON);

					if($response['status'] == 'success')
					{
						//patch the discoverable field (switching to false via metadata update does not work...)
						$patches = array(array(
							"op" => "replace",
							"path" => "/discoverable",
							"value" => "false"
							)
						);

						//change to JSON
						$patchesJSON = json_encode($patches);

						$response = dspacePatchMetadata('items', $duplicateUUID, $patchesJSON);

						if($response['status'] == 'success')
						{
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
								}
								else
								{
									$status = 'error';

									$message .= 'Error marking duplicate record as nondiscoverable: '.$duplicateHandle.' -- record: '.print_r($record, TRUE);
								}
							}
							else
							{
								$status = 'error';

								$message .= 'Error getting updated duplicate record: '.$duplicateHandle.' -- response: '.print_r($response, TRUE);
							}
						}
						else
						{
							$status = 'error';

							$message .= 'Error patching duplicate record: '.$duplicateHandle.' -- response: '.print_r($response, TRUE);
						}
					}
					else
					{
						$status = 'error';

						$message .= 'Error updating duplicate record: '.$duplicateHandle.' -- response: '.print_r($response, TRUE);
					}
				}
				else
				{
					$status = 'error';

					$message .= 'Error getting duplicate record: '.$duplicateHandle.' -- response: '.print_r($response, TRUE);
				}
			}
			else
			{
				$status = 'error';

				$message .= 'Error getting updated main record: '.$duplicateHandle.' -- response: '.print_r($response, TRUE);
			}
		}
		else
		{
			$status = 'error';

			$message .= 'Error updating main record: '.$mainRecordHandle.' -- response: '.print_r($response, TRUE);
		}
	}
	else
	{
		$status = 'error';

		$message .= 'Error getting main record: '.$mainRecordHandle.' -- response: '.print_r($response, TRUE);
	}

	return array('status'=>$status, 'message' => $message);
}
