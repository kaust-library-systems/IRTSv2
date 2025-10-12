<?php
/*

**** This file is responsible for listing the departments of certain types.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function departments($mode, $from){
	
	#init 
	global $irts, $repository;
	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'departments Rows Added' => 0
	];

	//delete all rows (always replace all data, regardless of mode)
	$result = $repository->query("TRUNCATE TABLE departments");	

	$typesAndLabels = array(
		'centerOfExcellence' => 'Center of Excellence',
		'corelab' => 'Core Lab',
		'corelabunit' => 'Core Lab',
		'corelabunitteam' => 'Core Lab',
		'academicDivision' => 'Division',
		'division' => 'Division',
		'program' => 'Program',
		'researchcenter' => 'Research Center',
		'researchplatform' => 'Research Platform'
	);

	$fieldsAndLabels = array(
		'Collection Handle' => 'dspace.collection.handle',
		'Community Handle' => 'dspace.community.handle',
		'Department Name' => 'local.org.name',
		'Department Abbreviation' => 'local.org.shortName'
	);
	
	//limit to departments with a DSpace collection (not only the 3 academic divisions have the type "division")
	$departmentIDs =  getValues($irts, 
		"SELECT DISTINCT `idInSource` FROM `metadata` 
			WHERE source = 'local' 
			AND field = 'local.org.type' 
			AND value IN('".implode("','", array_keys($typesAndLabels))."')
			AND idInSource IN(
				SELECT `idInSource` FROM `metadata` 
				WHERE source = 'local' 
				AND field = 'dspace.collection.handle' 
				AND `deleted` IS NULL 
			)
			AND `deleted` IS NULL", 
		array('idInSource'), 
		'arrayOfValues');
	
	foreach($departmentIDs as $departmentID){
		$recordTypeCounts['all']++;

		$row = array();

		$row['Department ID'] = $departmentID;

		$departmentType =  getValues($irts, 
			"SELECT `value` FROM `metadata` 
				WHERE source = 'local' 
				AND field = 'local.org.type' 
				AND idInSource = '$departmentID' 
				AND `deleted` IS NULL", 
			array('value'), 
			'singleValue');

		$row['Department Type'] = $typesAndLabels[$departmentType];
		
		foreach($fieldsAndLabels as $label => $field){
			$row[$label] = getValues($irts, 
				"SELECT `value` FROM `metadata` 
					WHERE source = 'local' 
					AND field = '$field' 
					AND idInSource = '$departmentID' 
					AND `deleted` IS NULL", 
				array('value'), 
				'singleValue');
				
			//Department field in repository using Collection Name - Use for relationship in Power BI
			if ($label = 'Collection Handle') {
					$row['Collection Name'] = getValues($irts, 
						"SELECT `value` FROM `metadata` 
							WHERE source = 'repository' 
							AND field = 'dspace.name' 
							AND idInSource = '".$row['Collection Handle']."'
							AND `deleted` IS NULL", 
						array('value'), 
						'singleValue');
				}
		}

		//insert row in departments table
		if(addRow('departments', $row)){
			$recordTypeCounts['departments Rows Added']++;
		}
	}
	
	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}