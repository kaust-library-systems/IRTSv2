<?php
/*

**** This file is responsible displaying the publisher page for PA.

** Parameters :
	$publisherID: Unique identifier for each publisher.

** Output : returns a HTML page.

*/

//------------------------------------------------------------------------------------------------------------

function getPublisherAgreements($publisherID){
	
	global $irts;
	$output = array();	
	
	// Check if ID has year appended (agreement ID combines publisher ID and year)
	if(strpos($publisherID, '-') === FALSE){
		$publisherID = $publisherID.'-%';
	}
	
	$publisherAgreements = getValues($irts, "SELECT `rowID` FROM `metadata` WHERE `source` = 'pa' AND `field` = 'pa.agreement' AND idInSource LIKE '$publisherID' AND `deleted` IS NULL", array('rowID'), 'arrayOfValues');
	
	foreach($publisherAgreements as $key => $agreement){		
		$type = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'pa' AND idInSource LIKE '$publisherID' AND `parentRowID` = '$agreement' AND `field` = 'pa.type' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		$output[$key]['pa.type'] = $type ;
				
		$eligibleauthors = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'pa' AND idInSource LIKE '$publisherID' AND `parentRowID` = '$agreement' AND `field` = 'pa.eligibleauthors' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		$output[$key]['pa.eligibleauthors'] = $eligibleauthors ;
		
		$startDate = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'pa' AND idInSource LIKE '$publisherID' AND `parentRowID` = '$agreement' AND `field` = 'pa.date.start' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		$output[$key]['pa.date.start'] = $startDate ;
		
		$endDate = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'pa' AND idInSource LIKE '$publisherID' AND `parentRowID` = '$agreement' AND `field` = 'pa.date.end' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		$output[$key]['pa.date.end'] = $endDate ;
		
		$notification = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'pa' AND idInSource LIKE '$publisherID' AND `parentRowID` = '$agreement' AND `field` = 'pa.notification' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		if(!empty($notification ))
			$output[$key]['pa.notification'] = $notification ;
	}
		
	return $output;
}