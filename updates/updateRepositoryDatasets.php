<?php

// This file is responsible for posting csv files to the repository containing public exports of basic metadata.

function updateRepositoryDatasets($report, $errors, $recordTypeCounts)
{	
	global $irts;

	//whether exports should be fully regenerated, or only updated with new and modified rows
	if(isset($_GET['regenerate']))
	{
		$regenerate = $_GET['regenerate'];
	}
	else
	{
		$regenerate = 'newAndModified';
	}

	//init
	$bundleName = 'ORIGINAL';
		
	//Get initial CSRF token and set in session
	$response = dspaceGetStatus();
			
	//Log in
	$response = dspaceLogin();

	if($response['status'] == 'success')
	{
		$response = dspaceListCommunityCollections(RESEARCH_COMMUNITY_UUID);

		if($response['status'] == 'success')
		{
			$results = json_decode($response['body'], TRUE);
		
			foreach($results['_embedded']['collections'] as $collection)
			{
				$recordTypeCounts['all']++;

				//start timer
				$getExportTime = microtime(true);
				
				$fileName = 'KAUST_Affiliated_'.$collection['name'].'_Basic_Metadata.csv';

				$report .= $fileName.PHP_EOL;

				$template = getExportTemplate($collection['name']);

				$output = getMetadataByCommunityOrCollection('collection', $collection['handle'], $template);
							
				if(!empty($output))
				{
					# create the file
					$filePath = UPLOAD_FILE_PATH.$fileName;	
					
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
			}
		}
	}

	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);		
}