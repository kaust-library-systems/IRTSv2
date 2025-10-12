<?php

/*

**** This file is responsible for posting a file to Repository Statistics And Metadata For Dashboards item.

** Parameters :
	csvfileURL: path of the file in the local dir
	fileName: string 
	
** Retune:
	None 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------


function updateRepositoryExportsAsCSV($report, $errors, $recordTypeCounts)
{	
	global $irts, $report, $recordTypeCounts;
	
	//init
	$recordTypeCounts['exports'] = 0;
	
	# export functions
	//$exports = array('getListOfPublicationsForVOSViewer' => 'listOfPublicationsForVOSViewer');
	$exports = array('getPlantGrowthCoreLabRelatedPublications' => 'listOfPlantGrowthCoreLabRelatedPublications');
	
	foreach($exports as $exportFunction => $exportName)
	{
		$output = call_user_func($exportFunction, $report, $errors, $recordTypeCounts);
		$fileName = $exportName.'.csv';			
	
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
			
			$summary = saveReport('repository', $report, $recordTypeCounts, $errors);
		}
	}
	
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);		
}