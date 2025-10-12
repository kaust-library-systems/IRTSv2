<?php
	//Define function to move deleted sourceData to deletedSourceData table (to help with table and index size problems in main table)
	function moveDeletedSourceData($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$count = 0;

		while($count < 1550000)
		{
			$count = $count+1000;
			
			$irts->query("INSERT INTO deletedSourceData SELECT * FROM `sourceData` WHERE `deleted` IS NOT NULL LIMIT 1000");

			$irts->query("DELETE FROM `sourceData` WHERE `deleted` IS NOT NULL LIMIT 1000");
			
			//sleep(2);
			set_time_limit(0);
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
