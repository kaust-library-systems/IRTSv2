<?php
/*

**** This file is responsible for retrieving the items for each program.

** Parameters :
	No parameters required
	
** Retune:
	output: associated array 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function programs($report, $errors, $recordTypeCounts){
	
	#init 
	global $irts;
	$source = 'repository';
	$field = 'Program';
	$output = array();
	$recordTypeCounts['programRows'] = 0;
	
	# first row in the file - columns
	$firstRow = array('Handle', $field );
	array_push($output , $firstRow);
	
	$programIDs =  getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'local' AND field = 'local.org.type' AND value = 'program' AND `deleted` IS NULL ", array('idInSource'), 'arrayOfValues');
	
	foreach( $programIDs as $programID){		
		
		$programHandle =  getValues($irts, "SELECT `value` FROM `metadata` WHERE source = 'local' AND field = 'dspace.collection.handle' AND idInSource = '$programID' AND `deleted` IS NULL ", array('value'), 'singleValue'); 
		
		$programName =  getValues($irts, "SELECT `value` FROM `metadata` WHERE source = 'local' AND field = 'local.org.name' AND idInSource = '$programID' AND `deleted` IS NULL ", array('value'), 'singleValue'); 
		
		if(!empty($programHandle)){
			
			$programItems = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source= '$source' AND field = 'dspace.collection.handle' AND value ='$programHandle' AND `deleted` IS NULL ", array('idInSource'), 'arrayOfValues');			
		
			# count the handles that will be checked
			$recordTypeCounts['all'] += count($programItems);
			$recordTypeCounts['programRows'] += count($programItems);
			
			foreach($programItems  as $programItem){
				
				$values = array();
				$values['Handle'] = $programItem;
				$values[$field ] = $programName;
				
				$row = array_values($values);
				array_push($output , $row);			
			}
		}		
	}
	
	$report .= 'Total program rows: '.$recordTypeCounts['programRows'].PHP_EOL;
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	return $output;	
}