<?php
/*

**** This file is responsible for setting flags for use in filtering and calculating other field values based on other metadata.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function itemFilters($mode, $from){
	
	#init 
	global $irts, $repository, $doiMinter;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'itemFilters Rows Deleted' => 0,
		'itemFilters Rows Added' => 0];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE itemFilters");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `itemFilters` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND idInSource IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dspace.type'
			AND value = 'item'
			AND `deleted` IS NULL
		)";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND `added` >= '$from'";
	}

	$handles = getValues($irts, $baseQuery." 	
		AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle){
		$recordTypeCounts['all']++;

		//check for existing row by handle and delete if it exists
		$result = $repository->query("DELETE FROM itemFilters WHERE `Item Handle` = '$handle'");
	
		$row = array();
		$row['Item Handle'] = $handle;
		$record = getRecord('repository', $handle, Null);

		//print_r($record);

		// check if publication date exists and if it starts with a valid year
		if(!empty($record['dc.date.issued'][0]['value']) && preg_match('/^\d{4}$/', substr($record['dc.date.issued'][0]['value'], 0, 4))){
			$row['Publication Year'] = substr($record['dc.date.issued'][0]['value'], 0, 4);

			//echo $row['Publication Year'];
		}
		else{
			$row['Publication Year'] = NULL;
		}

		// file deposited?
		$hasFile = FALSE;

		if(!empty($record['dspace.bundle.name'])){
			$hasFile = TRUE;

			/* foreach($record['dspace.bundle.name'] as $bundle){
				//print_r($bundle);

				if($bundle['value'] == 'ORIGINAL'){
					if(!empty($bundle['children'])){
						$hasFile = TRUE;
						break;
					}
				}
			} */
		}

		if($hasFile){		
			$row['File Deposited'] = 'Yes';	
		}
		else{
			$row['File Deposited'] = 'No';
		}

		//Check if the record has an active embargo
		$embargoEndDate = '';

		//embargo end date
		if(!empty($record['dc.rights.embargodate'][0]['value'])) {
			$embargoEndDate = $record['dc.rights.embargodate'][0]['value'];
		}

		//first check if embargo has expired
		if(!empty($embargoEndDate) && strtotime($embargoEndDate) > strtotime(date("Y-m-d"))){
			$activeEmbargo = 'Yes';
		}
		else{
			$activeEmbargo = 'No';
		}
		$row['File Under Embargo'] = $activeEmbargo;

		// assign an OA color and file type for records that have files
		$colorOfOA = 'Not OA';
		$fileType = 'No File';
		$version = '';
		$licenseURL =  '';

		if($hasFile){
			//version of file deposited
			if(!empty($record['dc.eprint.version'][0]['value'])) {
				$version = $record['dc.eprint.version'][0]['value'];				
			}

			//license URL (normally CC license)
			if(!empty($record['dc.rights.uri'][0]['value'])) {			
				$licenseURL =  $record['dc.rights.uri'][0]['value'];
			}

			if($version === "Publisher's Version/PDF" && (strpos($licenseURL, 'creativecommons') !== FALSE)) {
				$fileType = 'Publisher version with CC license';
				$colorOfOA = 'Gold';				
			}elseif($version === "Publisher's Version/PDF") {
				$fileType = 'Publisher version deposit allowed by journal policy';
				$colorOfOA = 'Green';
			}elseif($version === "Post-print") { // need new flag for source of file (publisher or author)
				$fileType = 'Accepted manuscript';
				//$fileType = 'Accepted manuscript deposited (from publisher)';
				$colorOfOA = 'Green';
			}elseif($version === "Post-print") {				
				//$fileType = 'Accepted manuscript deposited (from author after request)';
				$colorOfOA = 'Green';				
			}elseif($version === 'Pre-print') {
				$fileType = 'Preprint';
				$colorOfOA = 'Green';		
			}
			else{
				$fileType = 'Unknown';
				$colorOfOA = 'Unknown';
			}
		}
		else{
			$colorOfOA = '';
		}

		$row['Open Access Color'] = $colorOfOA;
		$row['Type of File Deposited'] = $fileType;
		
		//Has KAUST DOI
		$hasKAUSTDOI = 'No';

		if(!empty($record['dc.identifier.doi'])){
			foreach($record['dc.identifier.doi'] as $doi){
				$kaustDoi = getValues($doiMinter, "SELECT `doi` FROM `dois` WHERE `type` = 'production' AND `status` = 'active' AND `doi` = '".$doi['value']."'", array('doi'), 'singleValue');
				//print_r($match);

				if(!empty($kaustDoi))
				{
					$hasKAUSTDOI = 'Yes';
					break;
				}
			}
		}
		$row['Has KAUST DOI'] = $hasKAUSTDOI;

		//Has KAUST Faculty Author
		$hasKAUSTFacultyAuthor = 'No';

		if(!empty($record['kaust.person'])){
			foreach($record['kaust.person'] as $kaustAuthor){
				$match = checkPerson(array('name'=>$kaustAuthor['value']));
				//print_r($match);

				if(!empty($match['localID']))
				{
					$faculty = getValues($irts, "SELECT value
					FROM metadata
					WHERE source = 'local'
					AND idInSource = '".$match['localID']."'
					AND field = 'local.employment.type'
					AND value LIKE 'Faculty'
					AND deleted IS NULL", array('value'), 'singleValue');

					if(!empty($faculty))
					{
						$hasKAUSTFacultyAuthor = 'Yes';
						break;
					}
				}
			}
		}
		$row['Has KAUST Faculty Author'] = $hasKAUSTFacultyAuthor;

		$communities = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` 
			WHERE source = 'repository' 
			AND idInSource = '$handle'
			AND field = 'dspace.community.handle' 
			AND `deleted` IS NULL", array('value'), 'arrayOfValues');

		//Has KAUST affiliated author
		if(in_array('10754/324602', $communities) || in_array('10754/124545', $communities)){
			$kaustAffiliated = 'Yes';
		}
		else{
			$kaustAffiliated = 'No';
		}

		$row['Has KAUST Affiliated Author'] = $kaustAffiliated;

		//All authors are KAUST affiliated
		if(isset($record['kaust.person']) && isset($record['dc.contributor.author']) && count($record['kaust.person']) == count($record['dc.contributor.author'])){
			$allAuthorsKAUSTAffiliated = 'Yes';
		}
		elseif(in_array('10754/124545', $communities)){
			$allAuthorsKAUSTAffiliated = 'Yes';
		}
		else{
			$allAuthorsKAUSTAffiliated = 'No';
		}

		$row['All Authors are KAUST Affiliated'] = $allAuthorsKAUSTAffiliated;

		//Corresponding Author is KAUST Affiliated

		//KAUST Mention in Acknowledgement
		if(in_array('10754/581363', $communities)){
			$kaustAcknowledgement = 'Yes';
		}
		elseif(!empty($record['dc.description.sponsorship'][0]['value']) && strpos($record['dc.description.sponsorship'][0]['value'], 'KAUST') !== FALSE){
			$kaustAcknowledgement = 'Yes';
		}
		else{
			$kaustAcknowledgement = 'No';
		}

		$row['KAUST Mention in Acknowledgement'] = $kaustAcknowledgement;

		//KAUST Grant Number Acknowledgement
		if(!empty($record['kaust.grant.number'][0]['value'])){
			$kaustGrantNumberAcknowledgement = 'Yes';
		}
		else{
			$kaustGrantNumberAcknowledgement = 'No';
		}

		$row['KAUST Grant Number Acknowledgement'] = $kaustGrantNumberAcknowledgement;

		//KAUST Department or Lab Acknowledgement
		if(!empty($record['kaust.acknowledged.supportUnit'][0]['value'])){
			$kaustDepartmentOrLabAcknowledgement = 'Yes';
		}
		else{
			$kaustDepartmentOrLabAcknowledgement = 'No';
		}

		$row['KAUST Department or Lab Acknowledgement'] = $kaustDepartmentOrLabAcknowledgement;
		
		$row['Row Modified'] = getValues($irts, "SELECT `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND idInSource = '$handle' 
			AND parentRowID IS NULL
			AND `deleted` IS NULL
			ORDER BY added DESC LIMIT 1", array('added'), 'singleValue');

		//insert row in itemFilters table
		if(addRow('itemFilters', $row)){
			$recordTypeCounts['itemFilters Rows Added']++;
		}
	}

	// check for deleted handles
	if(!empty($from)){
		$deletedHandles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `sourceData` 
		WHERE source = 'repository'
		AND idInSource IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dspace.type'
			AND value = 'item'
			AND `deleted` IS NULL
		)
		AND idInSource NOT IN (
			SELECT `idInSource` FROM `sourceData` 
			WHERE source = 'repository' 
			AND `deleted` IS NULL
		)
		AND `deleted` >= '$from'", array('idInSource'), 'arrayOfValues');

		foreach($deletedHandles as $handle){
			$recordTypeCounts['all']++;

			//check for existing row by handle and delete if it exists
			$result = $repository->query("DELETE FROM itemFilters WHERE `Item Handle` = '$handle'");
			$recordTypeCounts['itemFilters Rows Deleted']++;
		}
	}	

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}