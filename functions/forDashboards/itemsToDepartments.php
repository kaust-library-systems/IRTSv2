<?php
/*

**** This file is responsible for listing the departments linked to each item handle.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function itemsToDepartments($mode, $from){
	
	#init 
	global $irts, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'itemsToDepartments Rows Deleted' => 0,
		'itemsToDepartments Rows Added' => 0
	];
	
	$fields = [
		'dc.contributor.department',
		'thesis.degree.discipline'
	];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE itemsToDepartments");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `itemsToDepartments` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('".implode("','", $fields)."')";

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
		$result = $repository->query("DELETE FROM itemsToDepartments WHERE `Item Handle` = '$handle'");

		//get the values of the fields for each handle
		$departments = getValues($irts, "SELECT `value`, `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field IN ('".implode("','", $fields)."') 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value', 'added'), 'arrayOfValues');

		foreach($departments as $department){
			$recordTypeCounts['all']++;

			$row = array();
			$row['Item Handle'] = $handle;
			$row['Department Name'] = $department['value'];
			$row['Row Modified'] = $department['added'];

			if(addRow('itemsToDepartments', $row)){
				$recordTypeCounts['itemsToDepartments Rows Added']++;
			}
		}
	}

	return [
		'report' => $report,
		'recordTypeCounts' => $recordTypeCounts,
		'errors' => $errors
	];
}