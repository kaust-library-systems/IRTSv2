<?php
// This function dc.description.provenance for sample of thesis per aduit request.

function exportThesisProvenance($report, $errors, $recordTypeCounts)
{	global $irts;

	$recordTypeCounts['success'] = 0;

	//start timer
	//$getExportTime = microtime(true);

	$output = array();
	
	$workFields = array(
		'log'=>'dc.description.provenance',
	);
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow = ['Handle'];
	$firstRow = array_merge($firstRow, array_keys($workFields));
	
	//column names
	$output[] = $firstRow;

	
	$query = "SELECT (`idInSource`) FROM `metadata`
WHERE `idInSource` IN (
    '10754/703355',
    '10754/703887',
    '10754/704018',
    '10754/704158',
    '10754/704214',
    '10754/704309',
    '10754/704334',
    '10754/704382',
    '10754/704417',
    '10754/704418',
    '10754/704442',
    '10754/704617'
    )
AND field LIKE 'dc.description.provenance'
AND deleted IS NULL;";
	

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
    

	foreach($handles as $handle)
	{
		$recordTypeCounts['all']++;

		echo $recordTypeCounts['all'].') '.$handle.PHP_EOL;

		$handleSuffix = explode('/', $handle)[1];

		
		
		$work = [];
        $work['Handle'] = $handle;
		
		
		
		foreach($workFields as $label=>$workField)
		{
			$work[$label] = preg_replace('/\s+/', ' ', implode('; ', getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $workField), array('value'), 'arrayOfValues')));
		}

		set_time_limit(0);
		ob_flush();
		$output[] = $work;
	}
	
	$fileName = 'ThesisLog.csv';

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



	
}