<?php
//Define function to save all person metadata in a Pure XML file
function preparePurePersonsXMLFile($persons)
{
	global $irts, $report;
	
	$status = 'complete';
	
	//file after conversion
	$output = fopen('/data/www/irts/public_html/upload/localPersons.xml', 'w');
	
	//convert to XML
	fwrite($output, '<?xml version="1.0" encoding="UTF-8"?><v1:persons xmlns:v1="v1.unified-person-sync.pure.atira.dk" xmlns:v3="v3.commons.pure.atira.dk">');
	
	//prepare the xml object that will include both valid and invalid entries
	$purexml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><v1:persons xmlns:v1="v1.unified-person-sync.pure.atira.dk" xmlns:v3="v3.commons.pure.atira.dk"/>');
	
	foreach($persons as $personID => $person)
	{
		//echo $personID.PHP_EOL;
		
		//print_r($person).PHP_EOL;
		
		//send the data to processing function
		$result = processPersonRecord($person, $purexml);
		
		$itemxml = $result['output']->asXML();
		
		fwrite($output, PHP_EOL.trim(preg_replace('/\t+/', '', $itemxml)));
	}

	fwrite($output, '</v1:persons>');
	
	fclose($output);
	
	return $status;
}
