<?php
/*

**** This file lists the review status and problem notes or reason for rejection

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function metadataReviewStatus($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'metadataReviewStatus Rows Added' => 0,
		'metadataReviewStatus Rows Deleted' => 0
	];

	//list of fields to be retrieved
	$fieldsAndLabels = array(
		'Type in IRTS' => 'dc.type',
		'Source' => 'irts.source',
		'ID in Source' => 'irts.idInSource',
		'DOI' => 'dc.identifier.doi',
		'Status' => 'irts.status',
		'Note' => ['irts.note', 'irts.rejectedReason'],
		'Harvest Basis' => 'irts.harvest.basis',
		'Processed By' => 'irts.processedBy'
	);
	
	$allFields = array();

	foreach($fieldsAndLabels as $label => $field){
		if(is_array($field)){
			foreach($field as $alternateField){
				$allFields[] = $alternateField;
			}
		}
		else{
			$allFields[] = $field;
		}
	}

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE metadataReviewStatus");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `metadataReviewStatus` WHERE 1", array('lastRowModified'), 'singleValue');
	}
	
	# get IRTS records from metadata table (except those logging update checks)
	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'irts'
		AND idInSource NOT LIKE 'itemType_%' 
		AND idInSource NOT LIKE 'repository_%' 
		AND field NOT LIKE 'irts.check%'
		AND field IN ('".implode("','", $allFields)."')";

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

	$irtsRecords = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');

	# loop through all IRTS records
	foreach($irtsRecords as $key => $idInIRTS){
		$report .= $key.') '.$idInIRTS.PHP_EOL;

		$recordTypeCounts['all']++;

		//check for existing row by idInIRTS and delete if it exists
		$result = $repository->query("DELETE FROM metadataReviewStatus WHERE `ID in IRTS` = '$idInIRTS'");

		$row = array();

		$row['ID in IRTS'] = $idInIRTS;

		//get the values of the fields for each idInIRTS
		foreach($fieldsAndLabels as $label => $fields){
			$value = '';
			if(is_array($fields)){
				foreach($fields as $field){
					$value = getValues($irts, "SELECT `value` FROM `metadata` 
						WHERE source = 'irts' 
						AND `idInSource` = '$idInIRTS' 
						AND `field` = '$field' 
						AND `deleted` IS NULL", array('value'), 'singleValue');
					if(!empty($value)){
						break;
					}
				}
			}
			else{
				$value = getValues($irts, "SELECT `value` FROM `metadata` 
					WHERE source = 'irts' 
					AND `idInSource` = '$idInIRTS' 
					AND `field` = '$fields' 
					AND `deleted` IS NULL", array('value'), 'singleValue');
			}
			$row[$label] = $value;
		}

		$doi = $row['DOI'];

		if(!empty($doi)){
			$row['Has DOI'] = 'Yes';

			$handle = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'repository' AND `field` = 'dc.identifier.doi' AND `value` = '$doi' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		}
		else{
			$row['Has DOI'] = 'No';

			$idInSource = $row['ID in Source'];

			$handle = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'repository' AND `field` IN(
				'dc.identifier.eid', 
				'dc.identifier.wosut', 
				'dc.identifier.pmid', 
				'dc.identifier.arxivid',
				'dc.identifier.github') AND `value` = '$idInSource' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		}

		//if handle is still empty, check for repository record by ID in IRTS
		if(empty($handle)){
			$handle = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'repository' AND `field` = 'kaust.identifier.irts' AND `value` = '$idInIRTS' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		}

		$row['Item Handle'] = $handle;

		if(!empty($handle)){
			$row['Has Repository Record'] = 'Yes';
		}
		else{
			$row['Has Repository Record'] = 'No';
		}

		$row['Date Harvested'] = getValues($irts, "SELECT `added` FROM `metadata` WHERE source = 'irts' AND `idInSource` = '$idInIRTS' AND `deleted` IS NULL ORDER BY added ASC LIMIT 1", array('added'), 'singleValue');
		$row['Date Processed'] = getValues($irts, "SELECT `added` FROM `metadata` WHERE source = 'irts' AND `idInSource` = '$idInIRTS' AND `field` = 'irts.status' AND `value` NOT LIKE 'inProcess' AND `deleted` IS NULL", array('added'), 'singleValue');

		// compare date harvested to date processed to get days in queue
		$dateHarvested = new DateTime($row['Date Harvested']);

		if(!empty($row['Date Processed'])){
			$dateProcessed = new DateTime($row['Date Processed']);
		}
		else{
			$row['Date Processed'] = NULL;

			$dateProcessed = new DateTime(NOW);
		}

		// Calculate the difference
		$interval = $dateHarvested->diff($dateProcessed);

		// Display the results
		$daysInQueue = $interval->format('%d');

		$row['Days in Queue'] = $daysInQueue;

		// Get the time elapsed for each item
		$timeElapsed = getValues($irts, "SELECT `value` FROM `metadata` 
			WHERE source = 'irts' 
			AND `field`= 'irts.process.timeElapsed' 
			AND idInSource = '$idInIRTS' 
			AND `deleted` IS NULL", array('value'), 'singleValue');
		
		if(!empty($timeElapsed)){
			$timeInarray = explode(':',  $timeElapsed);
			# total minutes = hours * 60 + mins + sec/60
			$timeElapsed = (floatval($timeInarray[0])*60)  + floatval($timeInarray[1]) + ( floatval($timeInarray[2]) / 60 ); # mins
		}
		else{
			$timeElapsed = NULL;
		}

		$row['Process Time in Metadata Review Form (Minutes)'] = $timeElapsed;

		$row['Row Modified'] = getValues($irts, 
			"SELECT `added` FROM `metadata` 
				WHERE source = 'irts'
				AND `idInSource` = '$idInIRTS'
				AND `field` IN ('".implode("','", $allFields)."')
				AND `deleted` IS NULL
				ORDER BY `added` DESC
				LIMIT 1", 
			array('added'), 
			'singleValue'
		);

		//insert row in metadataReviewStatus table
		if(addRow('metadataReviewStatus', $row)){
			$recordTypeCounts['metadataReviewStatus Rows Added']++;
		}
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}