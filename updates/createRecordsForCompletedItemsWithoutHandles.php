<?php
	//Define function to create repository records if an IRTS record is marked as completed and there is no corresponding record with a handle in the repository
	function createRecordsForCompletedItemsWithoutHandles($report, $errors, $recordTypeCounts) {
		global $irts;

		//Get all the IRTS records marked as completed that have no corresponding repository record based on DOI matching
		/* $completedItemsWithoutHandles = getValues(
			$irts,
			"SELECT DISTINCT `idInSource` FROM `metadata` 
				WHERE `source` = 'irts' 
				AND `field` = 'irts.status' 
				AND `value` = 'completed'
				AND `idInSource` IN (
					SELECT DISTINCT `idInSource` FROM `metadata` 
						WHERE `source` = 'irts' 
						AND `field` = 'dc.identifier.doi'
						AND `deleted` IS NULL
						AND `value` NOT IN (
							SELECT DISTINCT `value` FROM `metadata` 
								WHERE `source` = 'repository' 
								AND `field` = 'dc.identifier.doi'
								AND `deleted` IS NULL
							)
					)",
			array('idInSource'),
			'arrayOfValues'); */
		
		$completedItemsWithoutHandles = getValues(
			$irts,
			"SELECT DISTINCT `idInSource` FROM `metadata` 
				WHERE `source` = 'irts' 
				AND `field` = 'dc.identifier.doi'
				AND `value` = '10.1016/s0140-6736(24)00933-4'
				AND deleted IS NULL",
			array('idInSource'),
			'arrayOfValues');

		foreach($completedItemsWithoutHandles as $idInIRTS)
		{
			$recordTypeCounts['all']++;

			$itemReport = $recordTypeCounts['all'].') '.$idInIRTS.PHP_EOL;

			//Get initial CSRF token and set in session
			$response = dspaceGetStatus();
			
			//Log in
			$response = dspaceLogin();

			if($response['status'] == 'success')
			{
				$template = prepareTemplate('Article');

				$record = prepareRecordForTransfer($template, $idInIRTS);

				//determine owning collection
				$result = determineOwningCollection($record);
				$owningCollectionID = $result['owningCollectionID'];
				$itemReport .= $result['message'];

				if(!empty($owningCollectionID))
				{
					$itemReport .= '-- Owning collection ID: '.$owningCollectionID.PHP_EOL;

					//use special handling if there are more than 500 authors
					if(count($record['dc.contributor.author']) > 500)
					{
						$itemReport .= '-- More than 500 authors, splitting into chunks of 500.'.PHP_EOL;

						$result = dspaceCreateRecordWithLongAuthorList($record, $owningCollectionID);
					}
				}
				else
				{
					$itemReport .= '-- Owning collection not found, skipping.'.PHP_EOL;
				}
			}
			else
			{
				$itemReport .= '<div class="alert alert-danger">Failed to log in, details below: 
					<details>
						<summary> - Failure Response: </summary>
						<pre> - '.print_r($response, TRUE).'</pre>
					</details>
				</div>';
			}
			
			echo $itemReport;
		}

		$report .= $itemReport;

		$summary = saveReport($irts,__FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
