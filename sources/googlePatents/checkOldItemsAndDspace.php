<?php

/*

**** This file is responsible of handling the patent retrieving and processing by calling different functions.

** Parameters :
	$itemID: unique identifier for the patent in Dspace.
	$recordTypeCounts: associative array.
	$token: Dspace token.
	$output: an associative array with the patentData
 

** Created by : Yasmeen Alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 3 March 2021 - 8:49 AM

*/

//--------------------------------------------------------------------------------------------

function checkOldItemsAndDspace($patentID, $token, $output, &$recordTypeCounts){
	
	
	// init
	global $irts;
	$handle ='';
	$rowID ='';

	
	if(!empty($patentID)){
		// return the patent back to it's origin 
		$patentIdOldFormat = googlePatentsToUniversal($patentID);
	
		
		// check if the iten already exist in the Dspace with the old format
		
		$item =  getValues($irts, "SELECT `idInSource`, rowID FROM `metadata` WHERE`source` = 'dspace' AND ( `field` = 'dc.identifier.patentnumber' OR `field` = 'dc.identifier.applicationnumber') AND value = '".$patentIdOldFormat."' AND `deleted` IS NULL", array('idInSource', 'rowID'), 'arrayOfValues');
		
		
		if(isset($item['idInSource'])) {
		
			$itemID = $item['idInSource'];
			$rowID = $item['rowID'];
			
			
			
			//if the query return something delete all the old field patentNumber 
			if(!empty($itemID) && !empty($rowID)){
				
				$irts->query("UPDATE `metadata` SET `deleted`='".date("Y-m-d H:i:s")."' WHERE rowID = '".$rowID."'");
				
			} 
			
		}
	
	}
	
	


	$collectionID = '32636';
	$fields = array('dc.identifier.applicationnumber', 'dc.identifier.patentnumber');
	$itemDetails = postDspace($patentID, $output, $collectionID, $fields ,$token, $recordTypeCounts);
	
	return $itemDetails;
}