<?php

/*

**** This file is responsible for retrieving Google Patents page html for a given patent number and save it to the database.

** Parameters :
	$patentNumber: patent number as stirng.
	$message: string.
	$type: $type: string by defualt it is granted or application



** Output : returns an associative


** Created by : Yasmeen 
** institute : King Abdullah University of Science and Technology | KAUST
** Date : 24 February 2021 - 11:33 AM

*/

//------------------------------------------------------------------------------------------------------------
	
function retrieveGooglePatentPage($numberToCheck, &$message)
{
	global $irts;
	
	
	//init
	$record = array();
	$html = new DOMDocument();
	$IsItKAUST =TRUE;
	$patentNumber = str_replace(array(' ', '-', ','), '', $numberToCheck);	
	$source = 'googlePatents';
	$kaust = strtolower('King Abdullah University of Science and Technology');
	$url = GOOGLE_PATENTS_URL.'patent/'.$patentNumber.'/en';
		


	$headers = get_headers($url);
	
	if(strpos($headers[0], '404')!==FALSE)
	{
		$message .= ' - No Google Patent result for ' . $url.PHP_EOL;
	}
	else
	{
		
		libxml_use_internal_errors(true);
		
		//suppress errors in case above headers check did not detect failure
		$retrievedHtml = @file_get_contents($url, false);
		
		
		// if the HTMl page exists
		if($retrievedHtml !== FALSE)
		{
			$html->loadHTML($retrievedHtml);

			// only save these data from meta section
			$fieldsToSave = array("DC.title",
			"DC.description", 
			"citation_patent_application_number" , 
			"citation_patent_number",
			"citation_patent_publication_number",
			"citation_pdf_url", 
			"DC.contributor", 
			'countryName',
			"priorityDate",
			"filingDate",
			"publicationDate",
			"ifiStatus");
	
			$record['googlePatents.id'][]['value']  =  $patentNumber;
			
			// foreach data in the meta section
			foreach($html->getElementsByTagName('meta') as $meta)
			{
				$name = $meta->getAttribute('name');
				$content = $meta->getAttribute('content');
				$scheme = $meta->getAttribute('scheme');

				if(in_array($name, $fieldsToSave)) {	
				
					if(strpos($name, 'DC.contributor')!== FALSE) {
						
						// // if the assignee is not KAUST break the loop and make the record null
						// if(strpos($scheme, 'assignee')!== FALSE  && (strpos(strtolower($content), $kaust) === FALSE ) ){

								
								// $IsItKAUST = FALSE;
								// return null;
								
							
							
						// }
						
					
						$record['DC.contributor'][$scheme][]['value'] = $content;
						$field = 'DC.contributor.'.$scheme;
						$value = $content;
						$place = count($record['DC.contributor'][$scheme]);
					
											
					} else {
						
				
						
						if( in_array($name, array('DC.description', 'DC.title'))!==FALSE ){
							
							$record[$name][]['value']   = $content;
							
						} else {
							
							// this is will help us to comapre the ids after saving them
							$record[$name][]['value']   = str_replace(array(' ', '-', ','), '', $content);
							
						}
						
						$field = $name;
						$value = $content;
	

						
					}
				}
			}
			
			
			if($IsItKAUST) {
				
				foreach($html->getElementsByTagName('time') as $time)
				{
					
					$datetime = $time->getAttribute('datetime');
					$itemprop = $time->getAttribute('itemprop');
					
				
					if(in_array($itemprop, $fieldsToSave) && !isset($record[$itemprop])){	
							
						$record[$itemprop][]['value']  = $datetime;
						$field = $itemprop;
						$value = $datetime;
						
					}
				}
	
				foreach($html->getElementsByTagName('dd') as $dd )
				{
					
					
					$itemprop = $dd ->getAttribute('itemprop');
					$content = $dd->textContent;
			
					
					if( strpos($itemprop, 'countryName')!==FALSE && !isset($record[$itemprop] )){	
							
					
						$record[$itemprop][]['value']  = $content;
						$field = $itemprop;
						$value = $content;
					
					}
					
				}
				
				
				
				// get teh patents IDs ( patent number ) 
				foreach($html->getElementsByTagName('tr') as $tr ){
					
							
					$itemprop = $tr->getAttribute('itemprop');
					
					if( strpos($itemprop, 'docdbFamily') !== FALSE ){
						
						// get all the publicationNumber that are under the docdbFamily section
						$content = $tr->getElementsByTagName('span')[0]->textContent;
						$record['docdbFamily'][]['value'] = $content;
					
					 }
					
					
				}
				
				

				foreach($html->getElementsByTagName('span') as $span )
				{
					
					
					$itemprop = $span ->getAttribute('itemprop');
					$content = $span->textContent;
					
				
					
					
					if( strpos($itemprop, 'ifiStatus')!==FALSE && !isset($record[$itemprop] )){	
						
						
						
						$record[$itemprop][]['value']  = $content;
						$field = $itemprop;
						$value = $content;
					
					
						
						
					}
					
					
				}
				
				
			}
			
			
		}
		
	}	
	
	
	//Save copy of item JSON
	$sourceDataAsJSON = json_encode($record);
	$sourceReport = '';
	$recordType = saveSourceData($sourceReport, $source, $patentNumber, $sourceDataAsJSON, 'JSON');
	
	
	//save the record under googlePatents source
	$functionReport = saveValues($source, $patentNumber, $record, NULL);

	return $record;
}
