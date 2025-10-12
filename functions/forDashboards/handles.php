<?php
/*

**** This file is responsible for listing handles of all types.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function handles($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'handles Rows Added' => 0,
		'handles Rows Deleted' => 0];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE handles");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `handles` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dspace.type')";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND (
			`added` >= '$from'
			OR
			`deleted` >= '$from')";
	}
	else{
		$baseQuery .= " AND `deleted` IS NULL";
	}

	$handles = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle){
		$recordTypeCounts['all']++;

		//check for existing row by handle and delete if it exists
		$result = $repository->query("DELETE FROM handles WHERE `Handle` = '$handle'");
		
		//get the values of the fields for each handle
		$row = array();
		
		$row['Handle'] = $handle;

		$type = getValues($irts, "SELECT `value`, `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dspace.type' 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value', 'added'), 'arrayOfValues');
		
		$row['Handle Type'] = $type[0]['value'];
		$row['Row Modified'] = $type[0]['added'];
	
		//insert row in handles table
		if(addRow('handles', $row)){
			$recordTypeCounts['handles Rows Added']++;
		}
	}

	// check for deleted handles
	if(!empty($from)){
		$deletedHandles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `sourceData` 
		WHERE source = 'repository'
		AND idInSource NOT IN (
			SELECT `idInSource` FROM `sourceData` 
			WHERE source = 'repository' 
			AND `deleted` IS NULL
		)
		AND `deleted` >= '$from'", array('idInSource'), 'arrayOfValues');

		foreach($deletedHandles as $handle){
			$recordTypeCounts['all']++;

			//check for existing row by handle and delete if it exists
			$result = $repository->query("DELETE FROM handles WHERE `Handle` = '$handle'");
			$recordTypeCounts['handles Rows Deleted']++;
		}
	}	

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}