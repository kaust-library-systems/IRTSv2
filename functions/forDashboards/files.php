<?php
/*

**** This file is responsible for listing the repository files.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function files($mode, $from){
	
	#init 
	global $irts, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'files Rows Deleted' => 0,
		'files Rows Added' => 0
	];
	
	//list of fields to be retrieved
	$fieldsAndLabels = array(
		'File Order' => 'dspace.bitstream.sid',
		'File URL' => 'dspace.bitstream.url',
		'File Name' => 'dspace.bitstream.name',
		'File Size in Bytes' => 'dspace.bitstream.size',
		'File Type' => 'dspace.bitstream.format',
		'File Description' => 'dspace.bitstream.description'
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
		AND field IN ('".implode("','", $allFields)."')";

	if(!empty($from)){
		$report .= "Starting from $from".PHP_EOL;

		$baseQuery .= " AND (
			`added` >= '$from'
			OR 
			`deleted` >= '$from'
		)";	
	}
	else{
		$baseQuery .= " AND `deleted` IS NULL";
	}

	$handles = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');

	foreach($handles as $handle){

		//delete all rows for this handle
		$result = $repository->query("DELETE FROM files WHERE `Item Handle` = '$handle'");

		//get the bundles for each handle
		$bundles =  getValues($irts, 
			"SELECT `rowID`,`value` FROM `metadata` 
				WHERE source = 'repository' 
				AND field = 'dspace.bundle.name' 
				AND idInSource = '$handle' 
				AND `deleted` IS NULL", 
			array('rowID', 'value'),
			'arrayOfValues');

		foreach($bundles as $bundle){

			$bundleRowID = $bundle['rowID'];
			$bundleName = $bundle['value'];

			//get the bitstreams for each bundle
			$bitstreamRowIDs =  getValues($irts, 
				"SELECT `rowID` FROM `metadata` 
					WHERE source = 'repository' 
					AND field = 'dspace.bitstream.uuid' 
					AND parentRowID = '$bundleRowID' 
					AND `deleted` IS NULL", 
				array('rowID'),
				'arrayOfValues');

			foreach($bitstreamRowIDs as $bitstreamRowID){

				$recordTypeCounts['all']++;
				
				$row = array();

				$row['Item Handle'] = $handle;
				$row['Bundle Name'] = $bundleName;

				foreach($fieldsAndLabels as $label => $field){
					$value = getValues($irts, 
						"SELECT `value` FROM `metadata` 
							WHERE source = 'repository' 
							AND field = '$field' 
							AND parentRowID = '$bitstreamRowID' 
							AND `deleted` IS NULL", 
						array('value'), 
						'singleValue');

					$row[$label] = $value;
				}

				$row['Row Modified'] = getValues($irts, "SELECT `added` FROM `metadata` 
					WHERE source = 'repository' 
					AND idInSource = '$handle' 
					AND parentRowID = '$bitstreamRowID' 
					AND field IN ('".implode("','", $allFields)."')
					AND `deleted` IS NULL
					ORDER BY added DESC LIMIT 1", array('added'), 'singleValue');

				if(!empty($row['File Order'])){
					//insert row in files table
					if(addRow('files', $row)){
						$recordTypeCounts['files Rows Added']++;
					}
				}
			}
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
			$result = $repository->query("DELETE FROM files WHERE `Item Handle` = '$handle'");
			$recordTypeCounts['files Rows Deleted']++;
		}
	}
	return [
		'report' => $report,
		'recordTypeCounts' => $recordTypeCounts,
		'errors' => $errors
	];
}