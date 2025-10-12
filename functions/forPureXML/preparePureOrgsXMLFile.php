<?php
//Define function to save all org metadata in a Pure XML file
function preparePureOrgsXMLFile($orgs)
{
	global $irts, $report;
	
	$status = 'complete';
	
	//file after conversion
	$output = fopen('/data/www/irts/public_html/upload/localOrgs.xml', 'w');
	
	//convert to XML
	fwrite($output, '<?xml version="1.0" encoding="UTF-8"?><v1:organisations xmlns:v1="v1.organisation-sync.pure.atira.dk" xmlns:v3="v3.commons.pure.atira.dk">');
	
	//prepare the xml object that will include both valid and invalid entries
	$purexml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><v1:organisations xmlns:v1="v1.organisation-sync.pure.atira.dk" xmlns:v3="v3.commons.pure.atira.dk"/>');
	
	foreach($orgs as $orgID => $org)
	{
		//send the data to processing function
		$result = processOrgRecord($org, $purexml);
		
		$itemxml = $result['output']->asXML();
		
		fwrite($output, PHP_EOL.trim(preg_replace('/\t+/', '', $itemxml)));
	}

	fwrite($output, '</v1:organisations>');
	
	fclose($output);
	
	return $status;
}
