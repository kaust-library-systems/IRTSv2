<?php
	//Define function to move deleted metadata to deletedMetadata table (to help with table and index size problems in main table)
	function moveDeletedMetadata($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$count = 0;

		while($count < 28000000)
		{
			$count = $count+1000;
			
			$irts->query("INSERT INTO deletedMetadata SELECT * FROM `metadata` WHERE `deleted` IS NOT NULL LIMIT 1000");

			$irts->query("DELETE FROM `metadata` WHERE `deleted` IS NOT NULL LIMIT 1000");
			
			//sleep(2);
			set_time_limit(0);
		}

		/* $continueMoving = TRUE;

		while($continueMoving)
		{
			$rowsToMove = getValues($irts, "SELECT rowID FROM `metadata` WHERE `deleted` IS NOT NULL LIMIT 500", array('rowID'));
			
			if(count($rowsToMove) < 500)
			{
				$continueMoving = FALSE;
			}

			foreach($rowsToMove as $rowID)
			{
				$existingRow = getValues($irts, "SELECT rowID FROM `deletedMetadata` WHERE `rowID` = '$rowID'", array('rowID'));
				if(empty($existingRow))
				{
					$irts->query("INSERT INTO deletedMetadata SELECT * FROM `metadata` WHERE `rowID` = '$rowID'");
				} 

				$irts->query("INSERT INTO deletedMetadata SELECT * FROM `metadata` WHERE `rowID` = '$rowID'");

				$irts->query("DELETE FROM `metadata` WHERE `rowID` = '$rowID'");
				
				//sleep(2);
				set_time_limit(0);
			}
		}	 */	

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
