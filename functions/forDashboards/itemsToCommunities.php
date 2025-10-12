<?php
/*

**** This file is responsible for listing the communities linked to each item handle.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function itemsToCommunities($mode, $from){
	
	#init 
	global $irts, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'itemsToCommunities Rows Added' => 0
	];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE itemsToCommunities");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `itemsToCommunities` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dspace.community.handle')";

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

		//check for existing rows by handle and delete (thus if one community is changed on an item, all communitys are cleared and then re-added)
		$result = $repository->query("DELETE FROM itemsToCommunities WHERE `Item Handle` = '$handle'");

		//get the community handles for each item handle
		$communityHandles = getValues($irts, "SELECT `value`, `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND field IN ('dspace.community.handle') 
			AND idInSource = '$handle' 
			AND `deleted` IS NULL", array('value', 'added'), 'arrayOfValues');

		foreach($communityHandles as $communityHandle){
			$recordTypeCounts['all']++;

			$row = array();
			$row['Item Handle'] = $handle;
			$row['Community Handle'] = $communityHandle['value'];
			$row['Row Modified'] = $communityHandle['added'];

			if(addRow('itemsToCommunities', $row)){
				$recordTypeCounts['itemsToCommunities Rows Added']++;
			}
		}
	}

	return [
		'report' => $report,
		'recordTypeCounts' => $recordTypeCounts,
		'errors' => $errors
	];
}