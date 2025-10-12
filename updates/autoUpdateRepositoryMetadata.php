<?php
	//Define function to run checks for missing metadata on repository items and update as needed
	function autoUpdateRepositoryMetadata($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$recordTypeCounts = array(
			'all'=>0,
			'failed' =>0
		);

		$autoUpdates = array(
			'dates',
			'citationField',
			'collections',
			'DOIs',
			'fromArxiv',
			'identifiers',
			'mergeDuplicates',
			'submitDates'
		);

		/* $autoUpdates = array(
			'dates',
			'citationField',
			'identifiers',
			'journalNames',
			'publisherNames',
			'submitDates'
		); */

		//if specific autoUpdate set as parameter
		if(isset($_GET['autoUpdate']))
		{
			//check that it is in the list of possible autoUpdates
			if(!in_array($_GET['autoUpdate'], $autoUpdates))
			{
				$error = 'Invalid autoUpdate parameter: '.$_GET['autoUpdate'].'. Must be one of: '.implode(', ',$autoUpdates);

				echo $error.PHP_EOL;

				$errors[] = $error;

				return array('changedCount'=>0,'summary'=>'Failed with error: '.$error);
			}
			else
			{
				$autoUpdates = array($_GET['autoUpdate']);

				$report .= 'Auto update type: '.$_GET['autoUpdate'].PHP_EOL;
			}
		}
		else
		{
			$report .= 'Auto update types: '.implode(', ',$autoUpdates).PHP_EOL;
		}

		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
		
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{	
			//go through each type of auto update
			foreach($autoUpdates as $autoUpdate)
			{
				$recordTypeCounts['all']++;
				
				$autoUpdateReport = 'Auto update type: '.$autoUpdate.PHP_EOL;

				$autoUpdateRecordTypeCounts = array(
					'all'=>0,
					'new'=>0,
					'modified'=>0,
					'deleted'=>0,
					'unchanged'=>0,
					'skipped' =>0,
					'failed' =>0
				);

				$function = 'autoUpdate'.ucfirst($autoUpdate);

				$autoUpdateReport .= 'Function: '.$function.PHP_EOL;

				include 'functions/autoUpdates/'.$function.'.php';

				$response = $function('', [], $autoUpdateRecordTypeCounts);

				$autoUpdateReport .= $response['report'];

				$autoUpdateRecordTypeCounts = $response['recordTypeCounts'];

				$errors = array_merge($errors, $response['errors']);

				$autoUpdateSummary = saveReport($irts, $function, $autoUpdateReport, $autoUpdateRecordTypeCounts, $response['errors']);

				$report .= $autoUpdateSummary;
			}
		}
		else
		{
			$errors[] = $response;
		}

		echo $report;

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all'],'summary'=>$summary);
	}
