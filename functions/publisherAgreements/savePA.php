<?php /*

**** This file is responsible of saving the Publisher Agreements into DB

** Parameters :
	- $publisherID: Unique identifier for each publisher.
	- $list: array of the agreements.
	- $notify: array of the notification.
	- $place: the place of the agreement.

*/

//------------------------------------------------------------------------//


function savePA($publisherID, $list, $notify, $place){
	
	global $irts;
	$report = '';
	$source = 'pa';
	$input = array();
	$j = 0;
	$len = sizeof($list) / 4;
	$agreementPlace = 0;
	$publisherName = '';
	$year = date("Y");
	$agreementYear= '';
		
	if(!empty($publisherID)){
		
		//get the publisher ID
		$publisherIDInfo = explode("-", str_replace('member_','',$publisherID));
		$publisherIDNoDate =  $publisherIDInfo[0];
		
		$publisherName = getValues($irts, "SELECT `value` FROM `metadata` WHERE `source` = 'crossref' AND `field` = 'crossref.member.name' AND idInSource = 'member_".$publisherIDNoDate."' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		// if any existing record marke it as deleted
		//$irts->query("UPDATE `metadata` SET `deleted`='".date("Y-m-d H:i:s")."' WHERE `idInSource`='".$publisherID."' and `source`='pa' and `deleted` IS NULL");
		
		/* $existingPublsiher =  "SELECT `rowID` FROM `metadata` WHERE `source` = 'pa' AND `field` = 'pa.agreement' AND idInSource = '".$publisherID."' AND `deleted` IS NULL";		
		
		if(!empty($existingPublsiher))
			$irts->query("UPDATE `metadata` SET `deleted`= '".date("Y-m-d H:i:s")."' WHERE `rowID` = '$publisherID'"); */
				
		// convert all characters to lowercase		
		$input['pa.publisher'][]['value'] = strtolower($publisherName);
		
		for($i = 0; $i < $len; $i++){			
			// prepare the input 
			$agreement = ( $agreementPlace + 1 ) ;
		
			$input['pa.publisher'][0]['children']['pa.agreement'][]['value'] = $agreement;
						
			$type = $list[$j++];
			$input['pa.publisher'][0]['children']['pa.agreement'][$agreementPlace]['children']['pa.type'][]['value'] = $type;
						
			$eligibleauthors = $list[$j++];
			$input['pa.publisher'][0]['children']['pa.agreement'][$agreementPlace]['children']['pa.eligibleauthors'][]['value'] = $eligibleauthors;
						
			$endStart = $list[$j++];
			$input['pa.publisher'][0]['children']['pa.agreement'][$agreementPlace]['children']['pa.date.start'][]['value'] = $endStart;
			
			$endDate = $list[$j++];
			$input['pa.publisher'][0]['children']['pa.agreement'][$agreementPlace]['children']['pa.date.end'][]['value'] = $endDate;
			
			if(isset($notify[$agreementPlace])){
				$input['pa.publisher'][0]['children']['pa.agreement'][$agreementPlace]['children']['pa.notification'][]['value'] = 'True';
			}
			
			// increase
			$agreementPlace++;
		}
		
		$agreementYear = date("Y", strtotime($endStart));
		// print("<pre>".print_r($input,true)."</pre>");
	
		$idInSource = $publisherIDNoDate.'-'.$agreementYear;
		saveValues($source, $idInSource, $input, NULL);
	}
}