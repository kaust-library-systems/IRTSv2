<?php
/*

**** This file defines the function responsible for creating/updating items in DSpace.

** Parameters :
	$record: an associative array with the item metadata.
	$idToCheck : id of this record in the source system.
	$fieldToCheck : standard field name in the format namespace.element.qualifier (can be array of field names if needed).
	$clearExistingMetadata : TRUE if all metadata (except for provenance) should be cleared from the existing record, FALSE if existing record may contain metadata that is not in the new record, but should be kept.
	$token: DSpace token. 

*/

//--------------------------------------------------------------------------------------------


function addToDSpace($record, $idToCheck, $fieldToCheck, $clearExistingMetadata, $token)
{
	global $irts;
	
	$report = '';
	
	// check if the item already exists
	if(!empty($fieldToCheck))
	{
		$fieldsStatement = '';
		
		// usign the fields
		if(is_array($fieldToCheck))
		{
			$fieldStatement = " field IN ('".implode("','", $fieldToCheck)."')";			
		} 
		else
		{
			$fieldStatement = ' field = '.$fieldToCheck;
		}
		
		// get the itemID from DSpace
		$itemID =  getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE`source` = 'dspace' AND ".$fieldStatement." AND value = '".$idToCheck."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');		
		
		// to check the inputID is the item ID in Dspace
		if(empty($itemID))
		{
			$itemID = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE`source` = 'dspace' AND idInSource = '".$idToCheck."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		}
	}
	
	// update 
	if(!empty($itemID))
	{		
		// get the handle
		$handle = str_replace('http://hdl.handle.net/', '', getValues($irts, "SELECT `value` FROM `metadata` 
			WHERE `source` = 'dspace' 
			AND `idInSource` LIKE '".$itemID."' 
			AND `field` = 'dc.identifier.uri' AND deleted Is NULL", array('value'), 'singleValue'));
	
		//get the item from Dspace
		$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);
		
		//if there is an item in Dspace
		if(is_string($json))
		{
			//add a provenance
			$metadata = appendProvenanceToMetadata($itemID, $input, __FUNCTION__);			

			//convert the array to json file
			$json = prepareItemMetadataAsDSpaceJSON($metadata);			
			
			if($clearExistingMetadata)
			{
				// clear the old metadata 
				$clearMetadata = clearMetadataForSpecifiedItemFromDSpaceRESTAPI($itemID, $token);
			}
		
			//update item metadata in DSpace
			$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
			
			//check success	
			if(is_string($response))
			{				
				$status = 'updated';

				$report = ' - Updated DSpace Item ID: '.$itemID.PHP_EOL;	
			}
			else
			{
				$status = 'failed';
				
				$report = $itemID.' failed to update item in Dspace';
			}
		}
		else
		{
			$status = 'failed';
			
			$report = $itemID.' failed to get the metadata from Dspace';
		}
	}		
	// if the item does not exist
	else
	{ // create
		$metadata = prepareItemMetadataAsDSpaceJSON($input, FALSE);
		
		$response = dspaceCreateItem(DEFAULT_COLLECTION_ID, $metadata);
		
		$responseAsArray = json_decode($response, TRUE);

		if(!empty($responseAsArray['id']))
		{
			$itemID = $responseAsArray['id'];
			$handle = $responseAsArray['handle'];
			
			$status = 'new';
			$report = ' - New DSpace Item ID: '.$itemID.PHP_EOL;
		}
		else
		{
			$status = 'failed';
			
			$report = $itemID.' failed to create new item in DSpace';
		}
	}
	
	if(in_array($status, array('updated', 'new')))
	{
		$typeCollectionID = TYPE_COLLECTION_IDS[$record['dc.type'][0]];
			
		$newCollections = array();
		$newCollections['id'] = $itemID;
	
		$newCollections['parentCollection']['id'] = $typeCollectionID;
		$newCollections['parentCollectionList'][]['id'] = $typeCollectionID;
		
		$newCollections = json_encode($newCollections, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
		
		$response = mapItemToCollections($newCollections, DEFAULT_COLLECTION_ID, $token);
		
		//Inserting pause as it seems that too many requests to the API too quickly can return a 500 internal server error from DSpace
		sleep(2);
		
		if(is_array($response))
		{
			$message .= 'FAILURE: <br> -- Response received to map request was: '.print_r($response,TRUE).'<br> -- Failed to map to: '.$newCollections.'<br> -- Check the item and move it to the correct collection if needed.';
		}
		
		//Inserting pause as it seems that too many requests to the API too quickly can return a 500 internal server error from DSpace
		sleep(2);
		
		//get the metadata for the item to save in the local database
		$metadataToBeSavedInDB = getItemMetadataFromDSpaceRESTAPI($itemID, $token);			
		
		//save it in the database
		$recordType = processDspaceRecord($itemID, $metadataToBeSavedInDB, $report);
		
		//set inverse relationships on any related items
		$result = setInverseRelations($itemID);
		
		if(!empty($result))
		{
			$message .= PHP_EOL.' - Set Inverse Relations Result: '.$result.PHP_EOL;
		}
	}
	
	// return the dspace internal item id, the handle, the function report, and the status
	return array('itemID'=>$itemID, 'handle'=>$handle, 'report' => $report, 'status' => $status);
}