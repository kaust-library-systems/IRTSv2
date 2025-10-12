<?php
/*

**** This file is responsible for listing DOIs registered through our DataCite membership.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function dois($mode, $from){
	
	#init 
	global $doiMinter, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'dois Rows Added' => 0,
		'dois Rows Deleted' => 0];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE dois");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `dois` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT `handle`, `doi`, `status`, `added` FROM `dois` WHERE `type` = 'production' AND `status` = 'active'";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND `added` >= '$from'";
	}

	$dois = getValues($doiMinter, $baseQuery, array('handle', 'doi', 'status', 'added'), 'arrayOfValues');
	
	foreach($dois as $doi){
		$recordTypeCounts['all']++;

		//check for existing row by doi and delete if it exists
		$result = $repository->query("DELETE FROM dois WHERE `DOI` = '".$doi['doi']."'");
		
		$row = array();

		$row['Item Handle'] = $doi['handle'];

		$row['DOI'] = $doi['doi'];

		$row['Status'] = $doi['status'];

		$row['Row Modified'] = $doi['added'];

		//insert row in dois table
		if(addRow('dois', $row)){
			$recordTypeCounts['dois Rows Added']++;
		}
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}