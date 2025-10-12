<?php
/*

**** This file is responsible for listing repository communities.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function communities($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'communities Rows Added' => 0
	];

	//always replace all rows regardless of mode
	$result = $repository->query("TRUNCATE TABLE communities");		

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dspace.type')
		AND value = 'community'
		AND `deleted` IS NULL";

	$handles = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');

	foreach($handles as $handle){
		$recordTypeCounts['all']++;
		
		//get the values of the fields for each handle
		$row = array();
		
		$row['Community Handle'] = $handle;

		$communityName = getValues($irts, "SELECT `value`,`added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dc.title' 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value','added'), 'arrayOfValues');

		$row['Community Name'] = $communityName[0]['value'];
		$row['Row Modified'] = $communityName[0]['added'];

		//insert row in communities table
		if(addRow('communities', $row)){
			$recordTypeCounts['communities Rows Added']++;
		}
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}