<?php
/*

**** This file is responsible for listing the collections linked to each item handle.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function itemsToCollections($mode, $from){
	
	#init 
	global $irts, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'itemsToCollections Rows Added' => 0
	];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE itemsToCollections");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `itemsToCollections` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dspace.collection.handle')";

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

		//check for existing rows by handle and delete (thus if one collection is changed on an item, all collections are cleared and then re-added)
		$result = $repository->query("DELETE FROM itemsToCollections WHERE `Item Handle` = '$handle'");

		//get the collection handles for each item handle
		$collectionHandles = getValues($irts, "SELECT `value`, `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field IN ('dspace.collection.handle') 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value', 'added'), 'arrayOfValues');

		foreach($collectionHandles as $collectionHandle){
			$recordTypeCounts['all']++;

			$row = array();
			$row['Item Handle'] = $handle;
			$row['Collection Handle'] = $collectionHandle['value'];
			$row['Row Modified'] = $collectionHandle['added'];

			if(addRow('itemsToCollections', $row)){
				$recordTypeCounts['itemsToCollections Rows Added']++;
			}
		}
	}

	return [
		'report' => $report,
		'recordTypeCounts' => $recordTypeCounts,
		'errors' => $errors
	];
}