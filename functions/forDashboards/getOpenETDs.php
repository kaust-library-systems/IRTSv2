<?php
/*

**** This function retrieves basic metadata for open access ETDs

** Parameters :
	$report: string object
	$errors: errors array
	$recordTypeCounts:  associated array with the types
	
** Return:
	output: associated array

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function getOpenETDs($report, $errors, $recordTypeCounts)
{
	#init 
	global $irts, $ioi;
	
	$verbose = FALSE;
	
	$source = 'repository';
	$output = array();
	
	$workFields = array(
		'Type'=>'dc.type', 
		'Title'=>'dc.title', 
		'Author'=>'dc.contributor.author', 
		'Advisor'=>'dc.contributor.advisor', 
		'Program'=>'thesis.degree.discipline', 
		'DOI'=>'dc.identifier.doi', 
		'Handle'=>'dc.identifier.uri', 
		'Publication Date'=>'dc.date.issued', 
		'Citation'=>'dc.identifier.citation', 
		'Abstract'=>'dc.description.abstract');
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow = array_keys($workFields);
	$firstRow[] = 'Link to PDF';
	$firstRow[] = 'Link to Extracted Text';
	$firstRow[] = 'Metadata Last Modified';
	
	//column names
	$output[] = $firstRow;

	//retrieve handles for all ETDs with public files
	$handles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = '$source' 
		AND field = 'dspace.community.handle' 
		AND value = '10754/124545' 
		AND `deleted` IS NULL 
		ORDER BY idInSource DESC", array('idInSource'), 'arrayOfValues');
	
	# get the list of embargoed handles
	$embargoedHandles = getValues($irts, "SELECT idInSource FROM metadata 
		WHERE `source` LIKE 'repository' 
		AND `field` LIKE 'dc.rights.embargodate' 
		AND `value` >= '".TODAY."' 
		AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');

	//only list unembargoed ETD PDFs
	$handles = array_diff($handles, $embargoedHandles);

	foreach($handles as $handle)
	{
		$work = [];
		
		foreach($workFields as $label=>$workField)
		{
			$work[$label] = preg_replace('/\s+/', ' ', getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'singleValue'));
		}
		
		//Get pdf url
		$pdfQuery = "SELECT value, added FROM `metadata` m
			WHERE `source` LIKE 'repository'
			AND `idInSource` LIKE '$handle'
			AND `field` LIKE 'dspace.bitstream.url'
			AND `value` NOT LIKE '%copyright%'
			AND `value` NOT LIKE '%approval%'
			AND `value` NOT LIKE '%result%'
			AND `value` NOT LIKE '%defense%'
			AND `value` LIKE '%.pdf'
			AND deleted IS NULL
			AND parentRowID IS NULL
			";
		
		if($verbose)
		{						
			$report .= 'PDF Query: '.$pdfQuery.PHP_EOL;
		}
		
		//Get URL for first bitstream that is a PDF and not a copyright or approval form
		$work['Link to PDF'] = getValues($irts, $pdfQuery, array('value'), 'singleValue');
		
		$pdfAdded = getValues($irts, $pdfQuery, array('added'), 'singleValue');
		
		if(!empty($work['Link to PDF']) && strpos($pdfAdded, TODAY) === FALSE)
		{
			$work['Link to Extracted Text'] = str_replace('/bitstream/', '/bitstream/handle/', preg_replace('/\/\d{1}\//', '/', $work['Link to PDF'])).'.txt';
		}
		else
		{
			$work['Link to Extracted Text'] = '';
		}
		
		//Get last modified stamp
		$lastModifiedQuery = "SELECT added FROM metadata
			WHERE source = 'repository'
			AND idInSource = '$handle'
			AND field IN('".implode("','",$workFields)."')
			ORDER BY added DESC LIMIT 1";
		
		if($verbose)
		{						
			$report .= 'Last Modified Query: '.$lastModifiedQuery.PHP_EOL;
		}
		
		$work['Metadata Last Modified'] = str_replace(' ', 'T', getValues($irts, $lastModifiedQuery, array('added'), 'singleValue'));
		
		$output[] = $work;
	} // end of the getting the data based on the handle

	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	return $output;
}