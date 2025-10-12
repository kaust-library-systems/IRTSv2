<?php
	//Define function to delete epersons who are inactive
	function removeInactiveEpersons($report, $errors, $recordTypeCounts)
	{
		global $irts, $repository;

		$recordTypeCounts['epersonsRemoved'] = 0;
		$recordTypeCounts['epersonsFailedToRemove'] = 0;

		$response = dspaceGetStatus();
				
		$response = dspaceLogin();

		if($response['status'] == 'success') {

			// get eperson IDs for epersons that should be deleted
			$epersonIDs = getValues(
				$repository,
				"SELECT `id` FROM `epersons` 
					WHERE `lastActive` is NULL
                    AND  `email` NOT LIKE 'registrarhelpdesk@kaust.edu.sa'",
				     array('id'),
					'arrayOfValues'

			);

			// loop through eperson IDs and delete eperson via DSpace API
			foreach($epersonIDs as $key => $epersonID) {
				$epersonReport = $key.') Removing eperson with ID: '.$epersonID.PHP_EOL;

				$response = dspaceDeleteEperson($epersonID);
				if($response['status'] == 'success') {
					$epersonReport .= '- removed successfully.'.PHP_EOL;

					$recordTypeCounts['epersonsRemoved']++;
				}
				else {
					$epersonReport .= '- failed to remove.'.PHP_EOL;
					$epersonReport .= '- API response: '.print_r($response, true).PHP_EOL;
					$errors[] = 'Error removing eperson with ID: '.$epersonID.'. Response: '.json_encode($response);

					$recordTypeCounts['epersonsFailedToRemove']++;
				}

				echo $epersonReport;

				$report .= $epersonReport;

				ob_flush();
				set_time_limit(0);
			}

			$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

			echo $summary;
		}

		return array('changedCount'=>$recordTypeCounts['epersonsRemoved'],'summary'=>$summary);
	}