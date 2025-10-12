<?php
/*

**** This file is responsible for listing the authors for each item handle.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function authors($mode, $from){
	
	#init 
	global $irts, $ioi, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'authors Rows Added' => 0
	];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE authors");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `authors` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND field IN ('dc.contributor.author')";

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

		//check for existing rows by handle and delete (thus if one author is changed on an item, all authors are cleared and then re-added)
		$result = $repository->query("DELETE FROM authors WHERE `Item Handle` = '$handle'");

		$authors = getValues(
			$irts, 
			"SELECT `rowID`, `value`, `place`, `added` FROM `metadata` 
				WHERE `source` = 'repository' 
				AND `idInSource` = '$handle'  
				AND `field` = 'dc.contributor.author' 
				AND `deleted` IS NULL 
				ORDER BY `place` ASC", 
			array('rowID', 'value', 'added'), 
			'arrayOfValues');

		$lastAuthorKey = array_key_last($authors);

		foreach($authors as $key => $author){
			$recordTypeCounts['all']++;

			$row = array();
			$row['Item Handle'] = $handle;
			$row['Author Name'] = $author['value'];

			if($key == 0){
				$row['Place'] = 'First';
			}
			elseif($key == $lastAuthorKey){
				$row['Place'] = 'Last';
			}
			else{
				$row['Place'] = 'Middle';
			}

			$matchingKaustPerson = getValues($irts,
				"SELECT `value` FROM `metadata` 
					WHERE `source` = 'repository' 
					AND `idInSource` = '$handle'
					AND `field` = 'kaust.person' 
					AND `value` = '".$irts->real_escape_string($row['Author Name'])."'
					AND `deleted` IS NULL", 
				array('value'), 
				'singleValue');
			
			if(!empty($matchingKaustPerson)){
				$row['KAUST Affiliated'] = 'Yes';
			}
			else{
				$row['KAUST Affiliated'] = 'No';
			}

			$row['ORCID'] = getValues($irts,
				"SELECT `value` FROM `metadata` 
					WHERE `source` = 'repository' 
					AND `idInSource` = '$handle'  
					AND `parentRowID` = '".$author['rowID']."'
					AND `field` = 'dc.identifier.orcid' 
					AND `deleted` IS NULL", 
				array('value'), 
				'singleValue');

			//check if ORCID is in link form
			if(strpos($row['ORCID'], 'https://orcid.org/') !== FALSE)
			{
				//remove first part of link, leave only ID
				$row['ORCID'] = str_replace('https://orcid.org/', '', $row['ORCID']);
			}
			elseif(strpos($row['ORCID'], 'orcid.org/') !== FALSE)
			{
				//remove first part of link, leave only ID
				$row['ORCID'] = str_replace('orcid.org/', '', $row['ORCID']);
			}

			//if ORCID format is invalid, use empty value
			if (!preg_match('/^(\d{4}-){3}\d{3}[\dX]$/i', $row['ORCID']))
			{
				$report .= $handle. ' - Invalid ORCID: '.$row['ORCID'].PHP_EOL;
				$row['ORCID'] = '';
			}

			if(!empty($row['ORCID'])){
				$putCode = getValues($ioi,
					"SELECT `putCode` FROM `putCodes` 
						WHERE `orcid` = '".$row['ORCID']."' 
						AND `localSourceRecordID` = 'repository_$handle'
						AND `deleted` IS NULL", 
					array('putCode'), 
					'singleValue');

				if(!empty($putCode)){
					$row['Pushed To ORCID'] = 'Yes';
				}
				else{
					$row['Pushed To ORCID'] = 'No';
				}
			}
			else{
				$row['Pushed To ORCID'] = 'No';
			}

			$row['Row Modified'] = $author['added'];

			if(addRow('authors', $row)){
				$recordTypeCounts['authors Rows Added']++;
			}
		}
	}

	return [
		'report' => $report,
		'recordTypeCounts' => $recordTypeCounts,
		'errors' => $errors
	];
}