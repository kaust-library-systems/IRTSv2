<?php
//Define function to harvest Lens.org metadata
function harvestLens($source)
{
	global $irts, $newInProcess, $errors;

	$report = '';

	$errors = array();

	//Record count variable
	$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);

	//Check for requested harvest types
	if(isset($_GET['harvestTypes']))
	{
		$harvestTypes = explode(',', $_GET['harvestTypes']);
	}
	else
	{
		//Default harvest types
		$harvestTypes = array('citations', 'patents', 'publications');
	}

	foreach($harvestTypes as $harvestType)
	{
		//query Lens API for updated records of this type
		$result = queryLens($harvestType);

		//merge record type counts
		foreach($recordTypeCounts as $type => $count)
		{
			$recordTypeCounts[$type] += $result['recordTypeCounts'][$type];
		}

		//Append summary to main report
		$report .= $result['summary'];
	}

	$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
}
