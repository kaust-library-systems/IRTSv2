<?php
/*

**** This file is responsible for listing repository collections.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function collections($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'collections Rows Added' => 0
	];

	//always replace all rows regardless of mode
	$result = $repository->query("TRUNCATE TABLE collections");		

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dspace.type')
		AND value = 'collection'
		AND `deleted` IS NULL";

	$handles = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle){
		$recordTypeCounts['all']++;
		
		//get the values of the fields for each handle
		$row = array();
		
		$row['Collection Handle'] = $handle;

		$collectionName = getValues($irts, "SELECT `value`,`added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dc.title' 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value','added'), 'arrayOfValues');

		if(!empty($collectionName)){
			$row['Collection Name'] = $collectionName[0]['value'];
			$row['Row Modified'] = $collectionName[0]['added'];

			//insert row in collections table
			if(addRow('collections', $row)){
				$recordTypeCounts['collections Rows Added']++;
			}
		}		
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}