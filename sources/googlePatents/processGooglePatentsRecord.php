<?php
/*

**** This function is responsible of processing the patent record.

** Parameters :
	$patentNumber: unique identifier for the patent. 
	$token: Dspace token.
	$recordTypeCounts: associative array.
	$source: string.

** Created by : Yasmeen alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 20 June 2021 - 12:50 PM

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------


function processGooglePatentsRecord($patentNumber, $source, $token ,&$recordTypeCounts)
{
	global $irts;
	
	//init 
	$output = array();
	$errors = array();
	$familyMembers = array();
	$report = '';
	$familyRecord = '';
	$sourceReport = '';


	$sourceReport .= 'Patent Number: '.$patentNumber.PHP_EOL;
	
	$data = retrieveGooglePatentPage($patentNumber, $sourceReport);

	
	 if(!empty($data)) {
		
		
		
		// get the docdbFamily before maping the data
		if(isset($data['docdbFamily']) ){
			
			$docdbFamily = $data['docdbFamily'];
			unset($data['docdbFamily']);
			
		}


		// map and save the data as irts
		$output = mapGooglePatentsRecord($data, $sourceReport);
		
		

		////////////// JUST TEMP Just Temp: get the authors' names from Database ////////////////
		
		$dspaceID =  getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE`source` = 'dspace' AND ( `field` = 'dc.identifier.patentnumber' OR `field` = 'dc.identifier.applicationnumber') AND value = '".$patentNumber."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');

		$authors = getValues($irts, "SELECT value FROM `metadata` WHERE `idInSource` LIKE '".$dspaceID."' AND `field` = 'dc.contributor.author' AND `deleted` IS NULL", array('value'), 'arrayOfValues');
		
		$authorArray = array();
		
		foreach($authors as $author){
			
			$authorArray[]['value'] = $author;
			
		}
		
		$output['dc.contributor.author'] = $authorArray;
	
		
		$googlePatentID = $output['googlePatents.id'][0]['value'];
		
		///////////////// JUST TEMP ///////////////////////////////////////////////////////////
		
		
		
		
		
		$issuedDate = $output['dc.date.issued'][0]['value'];
		$title = $output['dc.title'][0]['value'];
		
	
		// save the output
		$functionReport = saveValues('irts', $source.'_'.$googlePatentID, $output, NULL);
		
	
		
		// Save it to the Dspace
		unset($output['googlePatents.id']);
		
		// check if the application in the Dspace
		$item = checkOldItemsAndDspace($googlePatentID, $token, $output, $recordTypeCounts);
		
		$report .= $item['report'];

		// Add the dspace id for each member in the family
		if(strpos($item['handle'], "/") !== FALSE) {
			
			$familyMembers[] = $item;

			// add the relation 
			$familyRecord['dc.relation.haspart'][]['value'] =  'Handle:'.$item['handle'];
			
		}



		// mark the patent number as checked
		$functionReport = saveValue('irts', 'googlePatents_'.$googlePatentID, 'irts.check.googlePatents', 1, date("Y-m-d H:i:s"), NULL);



		// create new items for the  docdbFamily 
		if(!empty($docdbFamily)){
			

			// for each applicationnumber
			foreach($docdbFamily  as  $docdbFamilyNumber ){
				
				
				$recordTypeCounts['all']++;
				$sourceReport .= 'docdbFamily Number: '.$docdbFamilyNumber['value'].PHP_EOL;

				
				// prepare the data to save the priority  number 
				$data = retrieveGooglePatentPage($docdbFamilyNumber['value'], $sourceReport);
				
				// get the docdbFamily before maping the data
				if(isset($data['docdbFamily']) ){
					
					$docdbFamily = $data['docdbFamily'];
					unset($data['docdbFamily']);
					
				}
				
				if(!empty($data)) {
					 
					 
					// map and save the data as irts
					$output = mapGooglePatentsRecord($data, $sourceReport);
					
					
					
					
					
					//////////////////////////// JUST TEMP ////////////////
					
					
					$output['dc.contributor.author'] = $authorArray;
					
					
					
					/////////////////////////// JUST TEMP ////////////////
					
					
					
					
					
					// check the latest title
					if($issuedDate < $output['dc.date.issued'][0]['value']){
						
						
						$issuedDate = $output['dc.date.issued'][0]['value'];
						$title = $output['dc.title'][0]['value'];
						
					}
							
					
					
					$googlePatentID = $output['googlePatents.id'][0]['value'];
					
					$functionReport = saveValues('irts', $source.'_'.$googlePatentID, $output, NULL);
		
					
		
					// Save it to the Dspace
					unset($output['googlePatents.id']);
					
					
					// chekc if the patent in the Dspace
					$item = checkOldItemsAndDspace($googlePatentID, $token, $output, $recordTypeCounts);
					
					$report .= $item['report'];
					
					// Add the dspace id for each member in the family
					if(strpos($item['handle'], "/") !== FALSE) {
						
						$familyMembers[] = $item;
			
						// add the relation 
						$familyRecord['dc.relation.haspart'][]['value'] =  'Handle:'.$item['handle'];
						
					}
					
					sleep(2);
					
				 } else {
		
		
					// if the retrieveGooglePatentPage returned emprty for an existing record it will
					// return empty record
					$sourceReport .= 'non-KAUST or emprty record'.$patentNumber.'<br>';
					$recordTypeCounts['non-KAUST or emprty record']++;
				
				
				}
				
				 
			}
			
	
			
		}
		
		
		
		// // Add the family relations to the record
		$familyRecord['dc.date.issued'][]['value'] = $issuedDate;
		$familyRecord['dc.title'][]['value'] = $title;
		// call to create the family record
		createFamilyRecordANDRelationsInDspace($familyMembers, $familyRecord, $token, $report,$recordTypeCounts);
		
		
		
		
		
	} else {
		
		
		// if the retrieveGooglePatentPage returned emprty for an existing record it will
		// return empty record
		$sourceReport .= 'non-KAUST or emprty record'.$patentNumber.'<br>';
		$recordTypeCounts['non-KAUST or emprty record']++;
	
	
	}
	
	return $report;
	
	
}