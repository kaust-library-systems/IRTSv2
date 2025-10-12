<?php
/*

**** This file is responsible for retrieving the items for each research center.

** Parameters :
	No parameters required
	
** Retune:
	None 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function researchCenters($report, $errors, $recordTypeCounts){
	
	#init 
	global $irts;
	$source = 'repository';
	$field = 'Research Center';
	$output = array();
	$recordTypeCounts['researchCenterRows'] = 0;
	
	# first row in the file - columns
	$firstRow = array('Handle', $field );
	array_push($output , $firstRow);
	
	$researchCentersIDs =  getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'local' AND field = 'local.org.type' AND value = 'researchcenter' AND `deleted` IS NULL ", array('idInSource'), 'arrayOfValues');
	
	foreach( $researchCentersIDs as $researchCenterID){		
		
		$researchCenterHandle =  getValues($irts, "SELECT `value` FROM `metadata` WHERE source = 'local' AND field = 'dspace.collection.handle' AND idInSource = '".$researchCenterID."' AND `deleted` IS NULL ", array('value'), 'singleValue'); 
		
		$researchCenterName =  getValues($irts, "SELECT `value` FROM `metadata` WHERE source = 'local' AND field = 'local.org.name' AND idInSource = '".$researchCenterID."' AND `deleted` IS NULL ", array('value'), 'singleValue'); 
		
		if(!empty($researchCenterHandle)){
			
			$researchCenterItems = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source= '".$source ."' AND field = 'dspace.collection.handle' AND value ='".$researchCenterHandle."' AND `deleted` IS NULL ", array('idInSource'), 'arrayOfValues');
		
			# count the handles that will be checked
			$recordTypeCounts['all'] += count($researchCenterItems);
			$recordTypeCounts['researchCenterRows'] += count($researchCenterItems);
			
			foreach($researchCenterItems as $researchCenterItem){
				
				$values = array();
				$values['Handle'] = $researchCenterItem;
				$values[$field] = $researchCenterName;
				
				$row = array_values($values);
				array_push($output , $row);			
			}
		}
		
	}
	
	$report .= 'Total dvision rows: '.$recordTypeCounts['researchCenterRows'].PHP_EOL;
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	return $output;
	
}