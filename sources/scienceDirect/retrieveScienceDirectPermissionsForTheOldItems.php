<?php

/**** This function is responsible of getting the right embargo for elsevier for the old items in the database.

** Parameters :
	No parameters required. 

*/

//-------------------------------------------------------------------------------------------

function retrieveScienceDirectPermissionsForTheOldItems(){	
	
	//init
	global $irts, $errors;
	$reportSourceData = '';
	$haveDifferentEmbargo = 'Have different embargo: ';
	$haveOALicense = 'Have OA license: ';
	$haveFileInTheRepository = 'Have file in the repository: ';
	$haveDifferentLicenseInTheRepository = 'Have different license in the repository : ';
	$noResultInScienceDirect = 'No result in scienceDirect: ';
	$noEmbargoInScienceDirect = 'No embargo in scienceDirect: ';
	$counter = 0 ;
	$report = '';
	$errors = array();	
	
	// report counts
	$recordTypeCounts = array('all'=>0,'Have different embargo'=>0,'Have OA license'=>0,'Have file in the repository'=>0,'Have different license in the repository'=>0, 'No result in scienceDirect' =>0, 'No embargo in the repository' =>0, 'No embargo in scienceDirect' => 0, 'unchanged'=>0);	

	
	
	
	// get all the elsevier's items doi
	$dois = getValues($irts, "SELECT
								  `value`
								FROM
								  `metadata`
								WHERE
								  `field` = 'dc.identifier.doi' AND `idInSource` IN(
								  SELECT
									`idInSource`
								  FROM
									`metadata`
								  WHERE
									`field` = 'dc.publisher' AND `value` LIKE 'Elsevier%' AND `source` = 'repository' AND `deleted` IS NULL
								) AND `deleted` IS NULL", array('value'), 'arrayOfValues');
									
	
	$recordTypeCounts['all'] = sizeof($dois);
	
	
	foreach($dois as $doi){
		
		// check if the item dosen't change
		$flag = false;
		// check the version if it is OA and the database is different then added to the process
		$eprintVersion = getValues($irts, "SELECT value FROM `metadata` WHERE `field` LIKE 'dc.eprint.version'  and `source` ='repository' AND `idInSource` = '$doi' AND `deleted` IS NULL", array('value'), 'singleValue');


		$permissions = retrieveScienceDirectArticleHostingPermissionsByDOI($doi);
		
		$data = print_r($permissions, True);
		$recordType = saveSourceData($irts, $reportSourceData, 'scienceDirect', $doi, $data, 'String');


		// if there is a result the array should contain a embargo key
		if(isset($permissions['embargo']) ){

				// count how many items have different embargo in the repository
				if(!is_null($permissions['embargo']) && !empty($embargo)) {

					if( $embargo != $permissions['embargo'] ){

					if($counter <= 50){
						$haveDifferentEmbargo.= "( DOI: ".$doi." )".PHP_EOL;
						$counter++;
					}
					$recordTypeCounts['Have different embargo']++;


					} else{

						$recordTypeCounts['No embargo in scienceDirect']++;
						if($counter <= 50){
						$noEmbargoInScienceDirect.= "( DOI: ".$doi." )".PHP_EOL;
						$counter++;
						}
					}
					
					$flag  = true;
				}


				// count how many item has OA in scienceDirect 
				if(!empty($permissions['audience'])){

					if($counter <= 50){
						$haveOALicense .= "( DOI: ".$doi." )".PHP_EOL;
						$counter++;
					}
					

					$recordTypeCounts['Have OA license']++;
					// count how many item has different license 
					if(strpos($eprintVersion, 'Publisher') == false) {

						$recordTypeCounts['Have different license in the repository']++;

						if($counter <= 50){
							$haveDifferentLicenseInTheRepository .= "( DOI: ".$doi." )".PHP_EOL;
							$counter++;
						}

					}

					//check if there is a file for the item
					$hasFile = getValues($irts, "SELECT value  FROM `metadata` WHERE `source` = 'repository' AND `field` = 'dspace.bitstream.url' AND idInSource = ( SELECT idInSource  FROM `metadata` WHERE `source` = 'repository' AND `field` = 'dc.identifier.doi' AND value = '$doi' AND `deleted` IS NULL ) AND `deleted` IS NULL ", array('value'));

					if(!empty($hasFile)){
							$recordTypeCounts['Have file in the repository']++;
		
						if($counter <= 50){
							$haveFileInTheRepository .= "( DOI: ".$doi." )".PHP_EOL;
							$counter++;
						}
					}
					
					$flag  = true;
				}
				
				
				if(!($flag)){
					
					$recordTypeCounts['unchanged']++;
					
				}

		 
			}else{

				$recordTypeCounts['No result in scienceDirect']++;
				if($counter <= 50){
					$noResultInScienceDirect .= "( DOI: ".$doi." )".PHP_EOL;
					$counter++;
				}
			
			}
		
	
			

	
	}
	
	
	$report .= $haveDifferentEmbargo.PHP_EOL;
	$report .=$haveOALicense.PHP_EOL;
	$report .=$haveFileInTheRepository.PHP_EOL; 
	$report .= $haveDifferentLicenseInTheRepository.PHP_EOL;
	$report .=$noResultInScienceDirect.PHP_EOL;
	$report .= $noEmbargoInScienceDirect.PHP_EOL;

	$summary = saveReport('scienceDirect', $report, $recordTypeCounts, $errors);
	print_r($summary);
}
