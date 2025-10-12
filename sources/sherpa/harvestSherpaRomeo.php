<?php
	//The Sherpa Romeo API v2 documentation is at: https://v2.sherpa.ac.uk/romeo/api.html
	
	function harvestSherpaRomeo($source)
	{
		global $irts, $newInProcess, $errors;

		$sourceReport = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0);
		
		while($recordTypeCounts['all'] < 3000)
		{
			$romeoResults = querySherpaRomeo('publisher', NULL, $recordTypeCounts['all']);
			if(empty($romeoResults['items']))
			{
				$sourceSummary = saveReport($irts, $source, $sourceReport, $recordTypeCounts, $errors);
				
				return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
			}
			else
			{
				foreach($romeoResults['items'] as $sourceData)
				{
					$recordType = processSherpaRomeoRecord($sourceData);
					$recordTypeCounts[$recordType]++;
					$recordTypeCounts['all']++;
				}
			}
		}
		return $recordType;
	}
