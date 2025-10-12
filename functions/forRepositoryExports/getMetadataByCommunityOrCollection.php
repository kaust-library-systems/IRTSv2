<?php
/*

**** This function retrieves basic metadata for datasets

** Parameters :
	$handleType: "community" or "collection"
	$handle: community or collection handle
	$template: template of labels and fields to use
	
** Return:
	output: associated array

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function getMetadataByCommunityOrCollection($handleType, $handle, $template)
{
	#init 
	global $irts;

	$report = '';

	$errors = array();

	//Record count variable
	$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0, 'skipped' =>0);
	
	$verbose = FALSE;
	
	$source = 'repository';
	$output = array();
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow[] = 'Link to Repository Record';
	$firstRow = array_merge($firstRow, array_keys($template));
	$firstRow[] = 'Metadata Last Modified';
	
	//column names
	$output[] = $firstRow;

	//retrieve handles for all datasets
	$handles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` 
		WHERE source = '$source' 
		AND field = 'dspace.$handleType.handle' 
		AND value = '$handle' 
		AND `deleted` IS NULL
		ORDER BY idInSource DESC", array('idInSource'), 'arrayOfValues');
	
	foreach($handles as $handle)
	{
		//echo $handle;
		
		$work = [];

		$work['Link to Repository Record'] = REPOSITORY_URL.'/handle/'.$handle;
		
		foreach($template as $label=>$workField)
		{
			$work[$label] = preg_replace('/\s+/', ' ', implode('; ',getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'arrayOfValues')));

			if(empty($work[$label]) && $label == 'Description/Abstract')
			{
				$work[$label] = preg_replace('/\s+/', ' ', implode('; ',getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.description'), array('value'), 'arrayOfValues')));
			}
		}
		
		//Get last modified stamp
		$lastModifiedQuery = "SELECT added FROM metadata
			WHERE source = 'repository'
			AND idInSource = '$handle'
			AND field IN('".implode("','",$template)."')
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