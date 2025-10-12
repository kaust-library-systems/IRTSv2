<?php
// This function exports metadata and public files in batches to the SDAIA export directory.
// The "from" parameter can be used to specify a date in the form YYYY-MM-DD to only retrieve records added after that date.

function exportPublicationsMetadataForFacultylist($report, $errors, $recordTypeCounts)
{	
	global $irts;

	$recordTypeCounts['success'] = 0;

	//start timer
	//$getExportTime = microtime(true);

	$output = array();
	
	$workFields = array(
		'Author'=>'dc.contributor.author',
		'Type'=>'dc.type', 
		'Title'=>'dc.title', 
		'DOI'=>'dc.identifier.doi', 
		'Publication Date'=>'dc.date.issued', 
		'KAUST Author'=>'kaust.person'
	);
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow = ['Handle'];
	$firstRow = array_merge($firstRow, array_keys($workFields));
	
	//column names
	$output[] = $firstRow;

	
	$query = "SELECT DISTINCT(`idInSource`) FROM `metadata` 
	WHERE `source` = 'repository' AND `field` = 'dc.identifier.doi'
	 AND `idInSource` IN (
	       SELECT `idInSource` FROM `metadata` 
		   WHERE `source` = 'repository' AND 
		   `field` = 'dspace.community.handle' AND 
		   `value` IN ('10754/324602') AND `deleted` IS NULL)";
	

// list of faculty add to the query , date is issue date 
	//$query ="SELECT DISTINCT * FROM `metadata` WHERE `source` = 'repository' AND 
	//`field` = 'dc.date.issued' AND `value` >= '2023-11' 
	//AND `idInSource` IN ( SELECT `idInSource` FROM `metadata` WHERE `source` = 'repository' 
	//AND `field` = 'dc.contributor.author' AND `value` IN ('Al-Babili, Salim','Blilou, Ikram','Hirt, Heribert', 'Mahfouz, Magdy M.', 
	//'Tester, Mark A.', 'Krattinger, Simon G.', 'Chodasiewicz, Monika','Poland, Jesse','Wada, Yoshihide', 'Wing, Rod Anthony', 
	//'Wulff, Brande') AND `deleted` IS NULL) 
	// AND `idInSource` IN ( SELECT `idInSource` FROM `metadata` WHERE `source` = 'repository' AND `field` = 'dc.type' AND `value`= 'Article' AND `deleted` IS NULL)
	//AND `deleted` IS NULL";


	//retrieve handles for research papers affiliated with KAUST and KAUST ETDs
	$handles = getValues($irts, $query, array('idInSource'), 'arrayOfValues');

	//list of files already in the directory (to avoid re-downloading)
	//$existingFiles = array_diff(scandir(SDAIA_EXPORT_DIRECTORY), array('..', '.'));

	//$existingHandleSuffixes = [];



	//print_r($existingHandleSuffixes);

	foreach($handles as $handle)
	{
		$recordTypeCounts['all']++;

		echo $recordTypeCounts['all'].') '.$handle.PHP_EOL;

		$handleSuffix = explode('/', $handle)[1];

		$fileName = $handleSuffix.'.pdf';
		
		$work = [];

		$work['Handle'] = 'http://hdl.handle.net/'.$handle;
		
		$work['File'] = $fileName;
		
		foreach($workFields as $label=>$workField)
		{
			$work[$label] = preg_replace('/\s+/', ' ', implode('; ', getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'arrayOfValues')));

			if($label == 'Type')
			{
				if(strpos($work[$label], '; ') !== FALSE)
				{
					$work[$label] = explode('; ', $work[$label])[0];
				}
			}
		}
		set_time_limit(0);
		ob_flush();
		$output[] = $work;
	}
	
	$fileName = 'PlublicationMetadata.csv';

	$report .= $fileName.PHP_EOL;

	if(!empty($output))
	{
		# create the file
		
		$filePath = "/data/www/irts/bin/export/".$fileName;	
		
		$csv = fopen($filePath, "w");

		foreach($output as $row)
		{					
			fputcsv($csv, $row);
		}
		
		fclose($csv);
	}

	//time elapsed in seconds
	//$getExportTime = microtime(true)-$getExportTime;

	//$report .= '- Time elapsed in seconds: '.$getExportTime.PHP_EOL;

	//$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

	//return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);		
}