<?php
	//Define function to remove permissions from files in ADMIN bundles
	function removePoliciesFromADMINFiles($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$recordTypeCounts['bitstreamsWithPolicies'] = 0;
		$recordTypeCounts['bitstreamsWithoutPolicies'] = 0;
		$recordTypeCounts['resourcePoliciesRemoved'] = 0;
		$recordTypeCounts['resourcePoliciesFailedToRemove'] = 0;

		//check for fromDate parameter
		if(!empty($_GET['fromDate']))
		{
			$fromDate = $_GET['fromDate'];
		}
		else //check for last mapping date
		{
			$fromDate = explode(' ', 
				getValues(
					$irts, 
					"SELECT `timestamp` FROM messages 
						WHERE process = 'removePoliciesFromADMINFiles' 
						AND `type` = 'summary' 
						ORDER BY `timestamp` 
						DESC LIMIT 1", 
					array('timestamp'), 
					'singleValue'))[0];
		}

		$report .= 'From Date: '.$fromDate.PHP_EOL;

		$adminBitstreamUUIDs = getValues(
			$irts, 
			"SELECT * FROM `metadata` 
				WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dspace.bitstream.uuid' 
				AND `added` > '$fromDate'
				AND `deleted` IS NULL
				AND parentRowID IN( 
					SELECT `rowID` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dspace.bundle.name' 
					AND `value` LIKE 'ADMIN' 
					AND `deleted` IS NULL
					)", 
			array('value'),
			'arrayOfValues'
		);

		foreach($adminBitstreamUUIDs as $bitstreamUUID) {
			$bitstreamReport = '';

			$response = dspaceGetStatus();
				
			$response = dspaceLogin();

			if($response['status'] == 'success') {
				$recordTypeCounts['all']++;

				$bitstreamReport .= PHP_EOL.$recordTypeCounts['all'].') Bitstream UUID: '.$bitstreamUUID.PHP_EOL;

				$existingPolicies = dspaceGetResourcePolicies('resource', $bitstreamUUID);

				usleep(100);

				if($existingPolicies['status'] == 'success') {
					$existingPolicies = json_decode($existingPolicies['body'], TRUE);

					$existingPolicy = FALSE;

					if(isset($existingPolicies['_embedded']['resourcepolicies'])) {
						foreach($existingPolicies['_embedded']['resourcepolicies'] as $policy) {
	
							$bitstreamReport .= '--- Existing policy id: '.$policy['id'].PHP_EOL;

							$response = dspaceDeleteResourcePolicy($policy['id']);

							usleep(100);

							if($response['status'] == 'success')
							{
								$recordTypeCounts['resourcePoliciesRemoved']++;
								
								$bitstreamReport .= '--- Policy removed'.PHP_EOL;
							}
							else
							{
								$recordTypeCounts['resourcePoliciesFailedToRemove']++;
								
								$bitstreamReport .= '--- Failed to remove policy: '.print_r($response, TRUE).PHP_EOL;
							}

							$existingPolicy = TRUE;
						}
					}

					if($existingPolicy) {
						$recordTypeCounts['bitstreamsWithPolicies']++;
					}
					else {
						$recordTypeCounts['bitstreamsWithoutPolicies']++;
						$bitstreamReport .= '--- No existing policies'.PHP_EOL;
					}
				}
				else
				{
					$bitstreamReport .= 'Failed to get existing policies'.PHP_EOL;
				}
			}
			else
			{
				$bitstreamReport .= 'Failed to log in'.PHP_EOL;
			}

			//echo $bitstreamReport;

			$report .= $bitstreamReport;

			ob_flush();
			set_time_limit(0);
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['resourcePoliciesRemoved'],'summary'=>$summary);
	}
