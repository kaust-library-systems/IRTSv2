<?php
	//Define function to save copy of a process report and summary into the messages table of the database (database will change depending on application - doiMinter, irts, ioi, etc.)
	function saveReport($database, $process, $report, $recordTypeCounts, $errors, $startTime = NULL)
	{
		$summary = '';
		
		$save = FALSE;
		
		//Check if there are any errors to report
		if(count($errors)!==0)
		{
			$save = TRUE;
		}

		//Check if there are any changed records to report
		if(isset($recordTypeCounts['unchanged']) && $recordTypeCounts['all']-$recordTypeCounts['unchanged']!==0)
		{
			$save = TRUE;
		}
		elseif(!isset($recordTypeCounts['unchanged']))
		{
			$save = TRUE;
		}

		//If report should be saved
		if($save)
		{
			//Create summary of process
			$summary = $process.':'.PHP_EOL;

			if($startTime)
			{
				//calculate time elapsed
				$endTime = microtime(TRUE);
				$elapsedTime = $endTime - $startTime;

				$summary .= ' - Time elapsed: '.round($elapsedTime, 2).' seconds'.PHP_EOL;
			}
			
			foreach($recordTypeCounts as $type => $count)
			{
				$summary .= ' - '.$count.' '.$type.PHP_EOL;
			}
			
			$summary .= ' - Error count: '.count($errors).PHP_EOL;
			
			foreach($errors as $error)
			{
				if(isset($error['type']))
				{
					$report .= ' - '.$error['type'].' error: '.$error['message'].PHP_EOL;
				}
				else
				{
					$report .= ' - '.print_r($error, TRUE).PHP_EOL;
				}
			}
			
			$report .= PHP_EOL.$summary;

			//Log process summary
			insert($database, 'messages', array('process', 'type', 'message'), array($process, 'summary', $summary));
			
			//Log full process report
			insert($database, 'messages', array('process', 'type', 'message'), array($process, 'report', $report));
		}
		
		return $summary;
	}
