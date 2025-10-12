<?php
/*

**** This file is responsible for retrieving the items for each division.

** Parameters :
	No parameters required
	
** Return:
	output: associated array 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function divisions($report, $errors, $recordTypeCounts)
{	
	#init 
	global $irts, $ioi;
	$source = 'repository';
	$field = 'Division';
	$output = array();
	$divisions = array("10754/126511"=>"BESE", "10754/126512"=>"CEMSE", "10754/126517"=>"PSE");
	
	# first row in the file - columns
	$firstRow = array('Handle', $field);
	array_push($output , $firstRow);
	
	//Retrieve handles for all items within the academic divisions community
	$handles = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = '$source' AND field = 'dspace.community.handle' AND value ='10754/126245' AND `deleted` IS NULL ", array('idInSource'), 'arrayOfValues');
	
	# count the handles that will be checked
	$recordTypeCounts['all'] += count($handles);
	$recordTypeCounts['divisionRows'] = 0;
	$report .= 'All items in academic divisions community: '.count($handles).PHP_EOL;	
	
	//Retrieve all of the divisions for each item
	foreach($handles as $handle)
	{
		$communityHandles = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND field = 'dspace.community.handle' AND idInSource = '$handle' AND `deleted` IS NULL ", array('value'), 'arrayOfValues');		
		
		if(!empty($communityHandles))
		{
			foreach($communityHandles as $communityHandle)
			{
				if(!empty($divisions[$communityHandle]))
				{
					$recordTypeCounts['divisionRows']++;
					
					$values = array();
					$values['handle'] = $handle;
					
					//Use the division abbreviation as the value
					$values[$field] = $divisions[$communityHandle];
					
					$row = array_values($values);
					array_push($output , $row);
				}
			}
		}		
	}
	
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	return $output;	
}