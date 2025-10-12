<?php
/*

**** This file is responsible for listing known ORCIDs.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function orcids($mode, $from){
	
	#init 
	global $irts, $ioi, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'orcids Rows Added' => 0,
		'orcids Rows Deleted' => 0];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE orcids");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `orcids` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`orcid`) FROM `orcids` 
		WHERE deleted IS NULL";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND (
							`added` >= '$from'
							OR 
							`orcid` IN (SELECT DISTINCT(`orcid`) FROM `tokens` WHERE `created` >= '$from')
							OR 
							`orcid` IN (SELECT DISTINCT(`orcid`) FROM `putCodes` WHERE `added` >= '$from')
						)";
	}

	$orcids = getValues($ioi, $baseQuery, array('orcid'), 'arrayOfValues');
	
	foreach($orcids as $orcid){
		$recordTypeCounts['all']++;

		//check for existing row by orcid and delete if it exists
		$result = $repository->query("DELETE FROM orcids WHERE `ORCID` = '$orcid'");
		
		//get the values of the fields for each handle
		$row = array();

		$row['ORCID'] = $orcid;

		$permissionsScope = getValues($ioi, "SELECT `scope` FROM `tokens` 
			WHERE `orcid` = '$orcid'
			AND `deleted` IS NULL
			ORDER BY created DESC LIMIT 1", array('scope'), 'singleValue');

		if(empty($permissionsScope)){
			$row['Permissions Granted'] = 'No';
		}
		else{
			$row['Permissions Granted'] = 'Yes';
		}

		//When permissions were last granted
		$tokenCreated = getValues($ioi, "SELECT `created` FROM `tokens` 
			WHERE `orcid` = '$orcid'
			AND `deleted` IS NULL
			ORDER BY created DESC LIMIT 1", array('created'), 'singleValue');
		
		if(!empty($tokenCreated)){
			$row['Permissions Granted Date'] = $tokenCreated;
		}
		else{
			$row['Permissions Granted Date'] = NULL;
		}
		
		$row['Permissions Scope'] = $permissionsScope;

		$permissionsExpiration = getValues($ioi, "SELECT `expiration` FROM `tokens` 
			WHERE `orcid` = '$orcid'
			AND `deleted` IS NULL
			ORDER BY created DESC LIMIT 1", array('expiration'), 'singleValue');

		if(empty($permissionsExpiration)){
			$row['Permissions Status'] = '';
		}
		elseif($permissionsExpiration > TODAY){
			$row['Permissions Status'] = 'Active';
		}
		else{
			$row['Permissions Status'] = 'Expired';
		}

		$row['Employment Entries Pushed to ORCID'] = getValues($ioi, "SELECT COUNT(*) FROM `putCodes` 
			WHERE `orcid` = '$orcid'
			AND `type` = 'employment'
			AND `deleted` IS NULL", array('COUNT(*)'), 'singleValue');

		$row['Education Entries Pushed to ORCID'] = getValues($ioi, "SELECT COUNT(*) FROM `putCodes` 
			WHERE `orcid` = '$orcid'
			AND `type` = 'education'
			AND `deleted` IS NULL", array('COUNT(*)'), 'singleValue');

		$row['Work Entries Pushed to ORCID'] = getValues($ioi, "SELECT COUNT(*) FROM `putCodes` 
			WHERE `orcid` = '$orcid'
			AND `type` = 'work'
			AND `deleted` IS NULL", array('COUNT(*)'), 'singleValue');

		//When the ORCID was first added to the orcids table
		$orcidAdded = getValues($ioi, "SELECT `added` FROM `orcids` 
			WHERE `orcid` = '$orcid'
			AND `deleted` IS NULL
			ORDER BY added DESC LIMIT 1", array('added'), 'singleValue');

		//Last time an entry was added to the putCodes table
		$entryAdded = getValues($ioi, "SELECT `added` FROM `putCodes` 
			WHERE `orcid` = '$orcid'
			AND `deleted` IS NULL
			ORDER BY added DESC LIMIT 1", array('added'), 'singleValue');

		if($entryAdded > $tokenCreated){
			$row['Row Modified'] = $entryAdded;
		}
		elseif($tokenCreated > $orcidAdded){
			$row['Row Modified'] = $tokenCreated;
		}
		else
		{
			$row['Row Modified'] = $orcidAdded;
		}

		//insert row in orcids table
		if(addRow('orcids', $row)){
			$recordTypeCounts['orcids Rows Added']++;
		}
	}

	// check for deleted orcids
	if(!empty($from)){
		$deletedORCIDs = getValues($ioi, "SELECT DISTINCT(`orcid`) FROM `orcids` 
			WHERE `deleted` >= '$from'", array('orcid'), 'arrayOfValues');

		foreach($deletedORCIDs as $deletedORCID){
			$recordTypeCounts['all']++;

			//check for existing row by orcid and delete if it exists
			$result = $repository->query("DELETE FROM orcids WHERE `ORCID` = '$deletedORCID'");

			$recordTypeCounts['orcids Rows Deleted']++;
		}
	}	

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}