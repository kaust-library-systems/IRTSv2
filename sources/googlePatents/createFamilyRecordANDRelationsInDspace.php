<?php /*

**** This file is responsible for creating Patents Family.

** Parameters :
	$token: Dspace token.
	$familyMembers: array contains all the Dspace IDs for one family.
	$recordTypeCounts: associative array.
	$report: stirng.



** Output :  report


** Created by : Yasmeen 
** institute : King Abdullah University of Science and Technology | KAUST
** Date : 6 June 2021 - 10:29 AM

*/

//------------------------------------------------------------------------------------------
	
function createFamilyRecordANDRelationsInDspace($familyMembers, $familyData, $token, &$report, &$recordTypeCounts) {
	
	// add typle 
	global $irts;
	$familyData['dc.type'][]['value'] = 'Patent';
	$familyData['dc.type'][]['value'] = 'Family Patent';
	$familyHandle  ='';
	$itemID ='';
	
	// to count the family record
	$recordTypeCounts['all']++;

	// check if there is already a family record
	foreach($familyMembers as $item) {
		
		// search in the family record if there is any member match
		$itemID = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE`source` = 'dspace' AND `field` = 'dc.relation.haspart' AND value = 'Handle:".$item['handle']."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
	
	}
	
	
	// create new one if not
	if(empty($itemID)) {
		
		
		$familyDetails = checkOldItemsAndDspace('', $token, $familyData, $recordTypeCounts);
		
		if(!empty($familyDetails['itemID'])) {
		
			
			$itemID = $familyDetails['itemID'];
			$report .= $familyHandle['report'];
		
		}
		
		
		
	
	} 
	
	
	sleep(10);
	
	// updated the exsiting family record
	// Add the relation field to the member's metadata ( member record ) 
	$familyData = setDisplayRelationsField($familyData)['record'];

	$familyDetails = checkOldItemsAndDspace($itemID, $token, $familyData, $recordTypeCounts);
	
	$familyHandle = $familyDetails['handle'];
	$report .= $familyDetails['report'];
	
	
	if(!empty($itemID)) {
		
		
		foreach($familyMembers as $item) {
			
			
			sleep(10);
			
			$dspaceID = $item['itemID'];
			$itemHandle = $item['handle'];
			
			//get the item from Dspace
			$json = getItemMetadataFromDSpaceRESTAPI($dspaceID, $token);
		
		
			//if there is an item in Dspace
			if(is_string($json)){
					
				$metadataArray = dSpaceJSONtoMetadataArray($json);
				
				
				// Add "is part of" relation
				$metadataArray['dc.relation.ispartof'][0]['value'] = 'Handle:'.$familyHandle ;
				
				
				//add a provenance
				$metadata = appendProvenanceToMetadata($dspaceID, $metadataArray, __FUNCTION__);
				
				
				// Add the relation field to the member's metadata ( member record ) 
				$metadata = setDisplayRelationsField($metadata)['record'];
				
				
				//convert the array to json file
				$json = prepareItemMetadataAsDSpaceJSON($metadata);
				
				
				$response = putItemMetadataToDSpaceRESTAPI($dspaceID, $json, $token);
				

				$field = '';
				// update the record in the database
				if(isset($metadataArray['dc.identifier.patentnumber'][0]['value'])){
					
					$field = 'dc.identifier.patentnumber';
					
				} else {
					
					$field = 'dc.identifier.applicationnumber';
					
				}
				
			
				if(is_string($response))
				{
					
					// save the relation
					$functionReport = saveValue('irts', 'googlePatents_'.$metadataArray[$field][0]['value'], 'dc.relation.ispartof', 1, 'Handle:'.$familyHandle, Null);
					$recordTypeCounts[$functionReport['status']]++;
					
					
				} else{
					
					$recordTypeCounts['failed']++;
					$report .= $dspaceID.' failed to update the data from Dspace';
				
					
				}
				 
			} else {
				
				$recordTypeCounts['failed']++;
				$report .= $dspaceID.' failed to retrieve the data from Dspace';
			
				
			}
			
		
		}
		
	
	} else {
			
			$recordTypeCounts['failed']++;
			$report .= '- failed to create family record';
			
			
	}
	
}


