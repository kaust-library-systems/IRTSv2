<?php
// This function exports metadata and thesis and dissertations files in batches to the SDAIA export directory.
// The "from" parameter can be used to specify a date in the form YYYY-MM-DD to only retrieve records added after that date.

function exportMetadataAndEtdFilesInBatches($report, $errors, $recordTypeCounts)
{	
	global $irts;

    echo "Starting to download ETDs";

	$recordTypeCounts['success'] = 0;

	//start timer
	$getExportTime = microtime(true);

	$output = array();
	
	$workFields = array(
		'Type'=>'dc.type', 
		'Title'=>'dc.title', 
		'Author'=>'dc.contributor.author', 
		'DOI'=>'dc.identifier.doi', 
		'Publication Date'=>'dc.date.issued', 
		'Repository Record Created'=>'dc.date.accessioned'
	);
	
	# first row in the file - columns - use if calculated fields are added...
	$firstRow = ['Handle','File'];
	$firstRow = array_merge($firstRow, array_keys($workFields));
	
	//column names
	$output[] = $firstRow;

	$query = "SELECT DISTINCT `idInSource` FROM `metadata`
		WHERE `source` = 'repository' 
		AND `field` = 'dc.type' 
		AND `value` IN ('Dissertation', 'Thesis') 
		AND `idInSource` IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE `source` = 'repository' 
			AND `field` = 'dspace.community.handle' 
			AND `value` IN ('10754/324602','10754/124545')
			AND `deleted` IS NULL  
		)
		AND `deleted` IS NULL";

    echo "mg. ". $query;

	//if a date is provided, only retrieve records added after that date (in form YYYY-MM-DD)
	if(isset($_GET['from']))
	{
		$query .= " AND `idInSource` IN (
			SELECT `idInSource` FROM `metadata` 
			WHERE `source` = 'repository' 
			AND `field` = 'dspace.bitstream.uuid' 
			AND value NOT IN (
				SELECT `value` FROM `metadata`
				WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dspace.bitstream.uuid' 
				AND `added` < '".$_GET['from']."'
			)
			AND `added` > '".$_GET['from']."'
			AND `deleted` IS NULL  
		)";
	}

    echo "Accessing the db";

	//retrieve handles for research papers affiliated with KAUST and KAUST ETDs
	$handles = getValues($irts, $query, array('idInSource'), 'arrayOfValues');
	
	# get the list of embargoed handles
	$embargoedHandles = getValues($irts, "SELECT idInSource FROM metadata 
		WHERE `source` LIKE 'repository' 
		AND `field` LIKE 'dc.rights.embargodate' 
		AND `value` >= '".TODAY."' 
		AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');

	//only list handles for records without an active embargo
	$handles = array_diff($handles, $embargoedHandles);

	echo 'Unembargoed handles count: '.count($handles).PHP_EOL;

	//list of files already in the directory (to avoid re-downloading)
	$existingFiles = array_diff(scandir(SDAIA_EXPORT_DIRECTORY), array('..', '.'));

	$existingHandleSuffixes = [];

	foreach($existingFiles as $existingFile)
	{
		$existingHandleSuffixes[] = explode('.', $existingFile)[0];
	}

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

		//Get file uuid
		$fileQuery = "SELECT value, added FROM `metadata`
		WHERE `source` LIKE 'repository'
		AND `idInSource` LIKE '$handle'
		AND `field` LIKE 'dspace.bitstream.uuid'
		AND deleted IS NULL
		AND parentRowID IN (
			SELECT rowID FROM metadata 
			WHERE `source` LIKE 'repository' 
			AND `field` LIKE 'dspace.bundle.name' 
			AND value LIKE 'ORIGINAL' 
			AND `deleted` IS NULL
		)
		AND rowID IN (
			SELECT parentRowID FROM metadata 
			WHERE `source` LIKE 'repository' 
			AND `field` LIKE 'dspace.bitstream.name' 
			AND value LIKE '%.pdf' 
			AND `deleted` IS NULL
		)
		";

		//avoid re-downloading files, but still list the record in the metadata file
		if(in_array($handleSuffix, $existingHandleSuffixes)) {
			$output[] = $work;
		}
		else {
			$fileUUIDs = getValues($irts, $fileQuery, array('value'), 'arrayOfValues');

			foreach($fileUUIDs as $fileUUID)
			{
				echo " - File UUID (".$fileUUID.").".PHP_EOL;

				$response = dspaceGetBitstreamsContent($fileUUID);
								
				if($response['status'] == 'success')
				{
					$fileContent = $response['body'];

					# create the file
					$filePath = SDAIA_EXPORT_DIRECTORY.$fileName;	

					if (file_put_contents($filePath, $fileContent) !== false)
					{
						echo " - File created (".$fileName.").".PHP_EOL;

						$recordTypeCounts['success']++;

						$output[] = $work;

						//break the loop after the first file is copied
						break;
					}				
				}
			}
		}

		set_time_limit(0);
		ob_flush();
	}
	
	$fileName = 'metadata.csv';

	$report .= $fileName.PHP_EOL;

	if(!empty($output))
	{
		# create the file
		$filePath = SDAIA_EXPORT_DIRECTORY.$fileName;	
		
		$csv = fopen($filePath, "w");

		foreach($output as $row)
		{					
			fputcsv($csv, $row);
		}
		
		fclose($csv);
	}

	//time elapsed in seconds
	$getExportTime = microtime(true)-$getExportTime;

	$report .= '- Time elapsed in seconds: '.$getExportTime.PHP_EOL;

	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);		
}

