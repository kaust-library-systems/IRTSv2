<?php
	//Define function to update repository record dc.date.submitted fields for the previous year if published in the first two months of the current year (FAAR must be completed by end of February and can include anything up until then)
	function autoUpdateSubmitDates($report, $errors, $recordTypeCounts)
	{
		global $irts;

		//if TODAY is before March 1st, update for the previous year
		if(date('m') < 3)
		{
			$currentYear = CURRENT_YEAR;

			$previousYear = $currentYear - 1;

			//get handles of items that have a issue date in the first two months of the current year and no other date in the previous year
			$handles = getValues(
				$irts, 
				"SELECT DISTINCT idInSource FROM metadata 
					WHERE source = 'repository'
					AND field = 'dc.date.issued'
					AND value LIKE '$currentYear%'
					AND deleted IS NULL
					AND idInSource NOT IN (
						SELECT idInSource FROM metadata 
						WHERE source = 'repository'
						AND field IN ('dc.date.submitted','dc.date.issued','dc.date.published-print','dc.date.published-online','dc.date.posted')
						AND value LIKE '$previousYear%'
						AND deleted IS NULL
					)",
				array('idInSource'),
				'arrayOfValues'
			);

			foreach($handles as $handle)
			{
				$itemReport = '';

				$flagged = FALSE;

				$changed = FALSE;

				$recordTypeCounts['all']++;

				$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL;

				$issueDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'repository' AND idInSource = '$handle' AND field = 'dc.date.issued' AND deleted IS NULL", array('value'), 'singleValue');

				$itemReport .= '-- dc.date.issued = '.$issueDate.PHP_EOL;

				$submittedDate = $previousYear;

				$itemReport .= '-- dc.date.submitted = '.$submittedDate.PHP_EOL;

				$newDates = array('dc.date.submitted' => array($submittedDate));

				$response = dspacePrepareAndApplyPatchToItem($handle, $newDates, __FUNCTION__);

				$recordTypeCounts[$response['status']]++;

				$itemReport .= $response['report'];

				$itemReport .= '-- '.$response['status'].PHP_EOL;

				$errors = array_merge($errors, $response['errors']);

				$report .= $itemReport;

				echo $itemReport;
			}
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
