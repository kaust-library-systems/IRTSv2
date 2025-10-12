<?php
/*

**** This function retrieves basic metadata for affiliated research outputs

** Parameters :
	$report: string object
	$errors: errors array
	$recordTypeCounts:  associated array with the types
	
** Return:
	output: associated array

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function getAffiliated($report, $errors, $recordTypeCounts)
{
	#init 
	global $irts, $ioi;
	
	$verbose = FALSE;
	
	$source = 'repository';
	$output = array();

	$fields = array('dc.date.issued', 'dc.type','dc.rights', 'dc.eprint.version', 'dspace.bitstream.url', 'dc.rights.embargodate', 'dc.publisher', 'dc.date.available');
	
	$workFields = array('Type'=>'dc.type', 'Title'=>'dc.title', 'Authors'=>'dc.contributor.author', 'Journal'=>'dc.identifier.journal', 'Publisher'=>'dc.publisher', 'DOI'=>'dc.identifier.doi', 'Handle'=>'dc.identifier.uri', 'Publication Date'=>'dc.date.issued', 'Citation'=>'dc.identifier.citation', 'Abstract'=>'dc.description.abstract', 'Link to License'=>'dc.rights.uri', 'Status'=>'pubs.publication-status');
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow = array_keys($workFields);
	$firstRow[] = 'Link to PDF';
	$firstRow[] = 'Link to Extracted Text';
	$firstRow[] = 'Metadata Last Modified';
	
	//column names
	$output[] = $firstRow;

	//retrieve handles for all items in affiliated or funded research communities
	$handles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE source= '$source' AND field = 'dspace.community.handle' AND value = '10754/324602' AND `deleted` IS NULL ORDER BY idInSource DESC", array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle)
	{
		$work = [];
		
		foreach($workFields as $label=>$workField)
		{
			if($label == 'Authors') //retrieve concatenated list of all authors
			{
				$work[$label] = preg_replace('/\s+/', ' ', implode('; ',getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'arrayOfValues')));
			}
			else //only single value retrieved for all other fields
			{
				$work[$label] = preg_replace('/\s+/', ' ', getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'singleValue'));
			}
		}
		
		if(empty($work['Status']))
		{
			if($work['Type']==='Preprint')
			{
				$work['Status'] = 'Under Review';
			}
			else
			{
				$work['Status'] = 'Published';
			}
		}
		else
		{
			if($work['Status'] === 'Submitted')
			{
				$work['Status'] = 'Under Review';
			}
			
			if($work['Status'] !== 'Published')
			{
				if($work['Type']!=='Preprint')
				{
					if(!empty($work['DOI']))
					{
						$work['Status'] = 'Published';
					}
				}
			}
		}
		
		//Get pdf url
		$pdfQuery = "SELECT value, added FROM `metadata` m
			WHERE `source` LIKE 'repository'
			AND `idInSource` LIKE '$handle'
			AND `field` LIKE 'dspace.bitstream.url'
			AND `place` LIKE '1'
			AND `value` LIKE '%.pdf'
			AND deleted IS NULL
			AND parentRowID IS NULL
			AND NOT EXISTS (
				SELECT rowID FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `idInSource` LIKE '$handle'
				AND `parentRowID` LIKE m.rowID
				AND `field` LIKE 'dspace.bitstream.accessRights'
				AND `value` LIKE 'restricted'
				AND deleted IS NULL
			)
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