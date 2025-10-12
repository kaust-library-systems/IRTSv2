 <?php
 
 function processBiosampleRecord($input, $accessionNumber)
{
	global $irts, $report;

	$source = 'ncbi';
	$record = array();

	$record['dc.type'][]['value'] =  'biosample';
	$record['dc.type'][]['value'] =  'Dataset';

	// convert the XML file to array
	$xml2array = xml2array($input);
	
	//print_r($xml2array);
	
	//var_dump($xml2array);

	// get the haspart relation
	if(isset($input->BioSample->Links->Link))
	{
		
		foreach($input->BioSample->Links->Link as $link)
		{
			 foreach($link ->attributes() as $key => $value)
			{
				//if (strpos($a, 'label') !== false)
				  if ( $key=='label')
				{ 
			        //$value = (string)($b->attributes());
					$record['dc.relation.ispartof'][]['value'] = 'bioproject:'.$value;
				}  
			}
		}
		
	}	
		if(isset($input->BioSample->Attributes->Attribute))
	{
		
		foreach($input->BioSample->Attributes->Attribute as $key => $value)

		{
			 
				//if (strpos($a, 'label') !== false)
				  if (preg_match('/[0-9]+\.[0-9]+ N [0-9]+\.[0-9]+ E/ ',$value,$loc))
				{ 
			        //$value = (string)($b->attributes());
					$location = explode(' ', $value, 3);
					$location[1] = $location[0] . ' ' . $location[1];
					array_shift($location);

					$record['dwc.location.decimalLatitude'][]['value'] = $location[0] ;
					$record['dwc.location.decimalLongitude'][]['value'] = $location[1] ;
				}  
			
		}
	}
	
// get the bioproject acession  that is associated with this biosample
	$bioprojectAcession = getValues($irts, "SELECT `value` FROM `metadata`
		WHERE `idInSource` IN (
			SELECT `idInSource` FROM `metadata`
			WHERE source = 'irts'
			AND (field = 'dc.relation.haspart'
			AND `value` LIKE 'biosample:$accessionNumber'
			AND `deleted` IS NULL)
			OR
			(field = 'dc.description.dataAvailability'
			AND `value` LIKE '%$accessionNumber%'
			AND `deleted` IS NULL)
		)
		AND field = 'dc.identifier.bioproject'
		AND `deleted` IS NULL", array('value'), 'arrayOfValues');

	foreach($bioprojectAcession as $bioprojectAcession)
	{
		$record['dc.relation.haspart'][] = 'bioproject:'.$bioprojectAcession;
	}

	$record = iterateOverBioSampleFields($record, $source, $xml2array);

	$record['dc.publisher'][]['value'] = 'NCBI';
	
	$record['dc.relation.url'][]['value'] = 'https://www.ncbi.nlm.nih.gov/biosample/?term='.$accessionNumber;

	
	return $record;
} 