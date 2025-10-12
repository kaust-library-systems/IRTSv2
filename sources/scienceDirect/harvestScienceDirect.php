<?php


/**** This function is responsible of harvesting the items from Elsevier.

** Parameters :
	No parameters required. 

*/

//--------------------------------------------------------------------------------------------

function harvestScienceDirect(){


	global $irts, $errors;

	//init 
	$errors = array();
	$today = date('Y-m-d H:i:s');
	$errors = array();

	$report = '';
	$reportSourceData = '';
	$haveDifferentEmbargo = 'Have different embargo: ';
	$haveOALicense = 'Have OA license: ';
	$haveFileInTheRepository = 'Have file in the repository: ';
	$haveDifferentLicenseInTheRepository = 'Have different license in the repository : ';
	$noResultInScienceDirect = 'No result in scienceDirect: ';
	$noEmbargoInTheRepository = 'No embargo in the repository: ';
	$noEmbargoInScienceDirect = 'No embargo in scienceDirect: ';
	$counter = 0 ;

	// report counts
	$recordTypeCounts = array('all'=>0,'Have different embargo'=>0,'Have OA license'=>0,'Have file in the repository'=>0,'Have different license in the repository'=>0, 'No result in scienceDirect' =>0, 'No embargo in the repository' =>0, 'No embargo in scienceDirect' => 0, 'unchanged'=>0);	


	// get all the Elsevier publication 
	$idInSource = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `field` = 'dc.publisher' and `value` LIKE 'Elsevier%' and `source` = 'repository' AND idInSource IN (SELECT `idInSource` FROM `metadata` WHERE `value` = '10754/324602' AND `field` ='dspace.community.handle' AND `deleted` IS NULL) AND  `idInSource` NOT IN (select idInSource from `sourceData` where source = 'scienceDirect' AND `deleted` IS NULL ) AND `deleted` IS NULL limit 1000", array('idInSource'), 'arrayOfValues');

	// all items
	$recordTypeCounts['all'] = count($idInSource);


	// for each doi get if the doi has embargo 
	foreach($idInSource as $id){


		//check the date if it's expired 
		// AND `value` < '".$today."' re-write it in the query after confirming it works
		$embargo = getValues($irts, "SELECT `value` FROM `metadata` where `idInSource` = '$id' AND `field` = 'dc.rights.embargodate' AND `source` = 'repository'  AND `deleted` IS NULL ", array('value'), 'singleValue');


		$doi = getValues($irts, "SELECT value  FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.identifier.doi' AND idInSource = '$id' AND `deleted` IS NULL", array('value'), 'singleValue');
		
	
		if(empty($embargo)){
			
			$recordTypeCounts['No embargo in the repository']++;
			if($counter <= 50){
				$noEmbargoInTheRepository.= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
				$counter++;
			}
		}

		// check the version if it is OA and the database is different then added to the process
		$eprintVersion = getValues($irts, "SELECT value FROM `metadata` WHERE `field` LIKE 'dc.eprint.version'  and `source` ='repository' AND `idInSource` = '$id' AND `deleted` IS NULL", array('value'), 'singleValue');

		
		$permissions = retrievescienceDirectArticleHostingPermissionsByDOI($doi);
		$data = print_r($permissions, True);
		$recordType = saveSourceData($irts, $reportSourceData, 'scienceDirect', $id, $data, 'JSON');

		// if there is a result the array should contain a embargo key
		if(isset($permissions['embargo']) ){

			// count how many items have different embargo in the repository
			if(!is_null($permissions['embargo']) && !empty($embargo)) {

				if( $embargo != $permissions['embargo'] ){

				if($counter <= 50){
					$haveDifferentEmbargo.= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
					$counter++;
				}
				$recordTypeCounts['Have different embargo']++;


				} else{

					$recordTypeCounts['No embargo in scienceDirect']++;
					if($counter <= 50){
					$noEmbargoInScienceDirect.= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
					$counter++;
					}
				}
			}


			// count how many item has OA in scienceDirect 
			if(!empty($permissions['audience'])){

				if($counter <= 50){
					$haveOALicense .= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
					$counter++;
				}
				

				$recordTypeCounts['Have OA license']++;
				// count how many item has different license 
				if(strpos($eprintVersion, 'Publisher') == false) {

					$recordTypeCounts['Have different license in the repository']++;

					if($counter <= 50){
						$haveDifferentLicenseInTheRepository .= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
						$counter++;
					}

				}

				//check if there is a file for the item
				$hasFile = getValues($irts, "SELECT value  FROM `metadata` WHERE `source` = 'repository' AND `field` = 'dspace.bitstream.url' AND idInSource = ( SELECT idInSource  FROM `metadata` WHERE `source` = 'repository' AND `field` = 'dc.identifier.doi' AND value = '$doi' AND `deleted` IS NULL ) AND `deleted` IS NULL ", array('value'));

				if(!empty($hasFile)){
						$recordTypeCounts['Have file in the repository']++;
	
					if($counter <= 50){
						$haveFileInTheRepository .= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
						$counter++;
					}
				}
			}


		
			}else{

				$recordTypeCounts['No result in scienceDirect']++;
				if($counter <= 50){
					$noResultInScienceDirect .= "(". "handle: ".$id. "DOI: ".$doi." )".PHP_EOL;
					$counter++;
				}
			
			}




	} // loop
	
	$report .= PHP_EOL.$haveDifferentEmbargo.PHP_EOL.$haveOALicense.PHP_EOL.$haveFileInTheRepository.PHP_EOL.$haveDifferentLicenseInTheRepository.PHP_EOL.$noResultInScienceDirect.PHP_EOL.$noEmbargoInTheRepository.PHP_EOL.
	$noEmbargoInScienceDirect.PHP_EOL;

	$summary = saveReport('scienceDirect', $report, $recordTypeCounts, $errors);
	
	





}
