<?php
/*

**** This file is responsible for preparing the basic metadata of repository items.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function items($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'items Rows Added' => 0,
		'items Rows Deleted' => 0];

	//list of fields to be retrieved
	$fieldsAndLabels = array(
		'DOI' => 'dc.identifier.doi', 
		'Item Type' => 'dc.type',
		'Title' => 'dc.title',
		'Publisher' => 'dc.publisher',
		'Venue' => [
			'dc.identifier.journal', 
			'dc.conference.name'
		],		
		'Record Visibility' => 'dspace.record.visibility',
		'Publication Date' => 'dc.date.issued',
		'Embargo End Date' => 'dc.rights.embargodate'
	);

	$allFields = array();
	foreach($fieldsAndLabels as $label => $field){
		if(is_array($field)){
			foreach($field as $alternateField){
				$allFields[] = $alternateField;
			}
		}else{
			$allFields[] = $field;
		}
	}

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE items");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `items` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$baseQuery = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = 'repository'
		AND idInSource IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dspace.type'
			AND value = 'item'
			AND `deleted` IS NULL
		)
		AND field IN ('".implode("','", $allFields)."')";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND `added` >= '$from'";
	}

	$handles = getValues($irts, $baseQuery." 	
		AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle){
		$recordTypeCounts['all']++;

		//check for existing row by handle and delete if it exists
		$result = $repository->query("DELETE FROM items WHERE `Item Handle` = '$handle'");
		
		//get the values of the fields for each handle
		$row = array();

		$row['Item Handle'] = $handle;

		foreach($fieldsAndLabels as $label => $fields){
			$value = '';
			if(is_array($fields)){
				foreach($fields as $field){
					$value = getValues($irts, "SELECT `value` FROM `metadata` 
						WHERE source = 'repository' 
						AND field = '$field' 
						AND idInSource = '$handle' 
						AND `deleted` IS NULL", array('value'), 'singleValue');

					if(!empty($value)){
						break;
					}
				}
			}else{
				$field = $fields;

				$value = getValues($irts, "SELECT `value` FROM `metadata` 
					WHERE source = 'repository' 
					AND field = '$field' 
					AND idInSource = '$handle' 
					AND parentRowID IS NULL
					AND `deleted` IS NULL
					ORDER BY rowID DESC", array('value'), 'singleValue');
			}

			//Make sure that publication dates and embargo end dates are less than 10 characters (use substr to shorten if too long)
			if($field == 'dc.date.issued' || $field == 'dc.rights.embargodate'){
				$value = substr($value, 0, 10);
			}

			//replace any line breaks with spaces to prevent issues with CSV formatting
			$row[$label] = str_replace(["\r\n", "\r", "\n"], ' ', $value);
		}

		$row['Row Modified'] = getValues($irts, "SELECT `added` FROM `metadata` 
			WHERE source = 'repository' 
			AND idInSource = '$handle' 
			AND parentRowID IS NULL
			AND field IN ('".implode("','", $allFields)."')
			AND `deleted` IS NULL
			ORDER BY added DESC LIMIT 1", array('added'), 'singleValue');

		//insert row in items table
		if(addRow('items', $row)){
			$recordTypeCounts['items Rows Added']++;
		}
	}

	// check for deleted handles
	if(!empty($from)){
		$deletedHandles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `sourceData` 
		WHERE source IN ('oai','repository')
		AND idInSource IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE source = 'repository' 
			AND field = 'dspace.type'
			AND value = 'item'
			AND `deleted` IS NOT NULL
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
			$result = $repository->query("DELETE FROM items WHERE `Item Handle` = '$handle'");
			$recordTypeCounts['items Rows Deleted']++;
		}
	}	

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}