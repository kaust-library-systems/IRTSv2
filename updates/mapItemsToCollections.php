<?php
	//Define function to map items to departmental or lab collections
	function mapItemsToCollections($report, $errors, $recordTypeCounts)
	{
		global $irts;

		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();

		if($response['status'] == 'success')
		{	
			//Log in
			$response = dspaceLogin();
			
			if($response['status'] == 'success')
			{
				$unmatched = array();

				$failed = array();

				$recordTypeCounts['mapped'] = 0;

				$recordTypeCounts['unmatched'] = 0;

				$recordTypeCounts['matched'] = 0;

				$recordTypeCounts['failed'] = 0;

				//check for fromDate parameter
				if(!empty($_GET['fromDate']))
				{
					$fromDate = $_GET['fromDate'];
				}
				else //check for last mapping date
				{
					$fromDate = explode(' ', getValues($irts, "SELECT `timestamp` FROM messages WHERE process = 'mapItemsToCollections' AND `type` = 'summary' ORDER BY `timestamp` DESC LIMIT 1", array('timestamp'), 'singleValue'))[0];
				}

				$report .= 'From Date: '.$fromDate.PHP_EOL;

				echo 'From Date: '.$fromDate.PHP_EOL;

				//get all items that have department entries added since the last mapping
				$itemIDs = getValues(
					$irts, 
					"SELECT DISTINCT `idInSource` FROM `metadata`
						WHERE `source` = 'dspace'
						AND `field` = 'dc.contributor.department'
						AND `added` > '$fromDate'
						AND `deleted` IS NULL",
					array('idInSource'),
					'arrayOfValues'
				);

				echo 'Items to map: '.count($itemIDs).PHP_EOL;

				foreach($itemIDs as $itemID)
				{					
					$recordTypeCounts['all']++;
					
					$itemReport = PHP_EOL.$recordTypeCounts['all'].') Item ID: '.$itemID.PHP_EOL;

					$collectionIDs = [];

					//get all department entries for the item
					$departments = getValues(
						$irts, 
						setSourceMetadataQuery('dspace', $itemID, NULL, 'dc.contributor.department', NULL),
						array('value'),
						'arrayOfValues'
					);

					foreach($departments as $department)
					{
						$itemReport .= 'Department: '.$department.PHP_EOL;
						
						//escape department string for use in MySQL query
						$department = $irts->real_escape_string($department);

						//get the collection ID for the department
						$collectionID = getValues(
							$irts, 
							"SELECT `idInSource` FROM `metadata`
								WHERE `source` = 'dspace'
								AND `field` = 'dspace.name'
								AND `value` = '$department'
								AND `deleted` IS NULL
								AND `idInSource` IN (
									SELECT `idInSource` FROM `metadata`
										WHERE `source` = 'dspace'
										AND `field` = 'dspace.type'
										AND `value` = 'collection'
										AND `deleted` IS NULL
								)",
							array('idInSource'),
							'singleValue'
						);

						if(!empty($collectionID))
						{
							$collectionIDs[] = $collectionID;

							$itemReport .= ' - Collection ID: '.$collectionID.PHP_EOL;

							$recordTypeCounts['matched']++;
						}
						else
						{
							$unmatched[] = $department;

							$itemReport .= ' - No matching collection found for: '.$department.PHP_EOL;

							$recordTypeCounts['unmatched']++;
						}
					}

					//if there are collections to map to
					if(!empty($collectionIDs))
					{
						$itemReport .= ' - Try mapping to Collections: '.print_r($collectionIDs, TRUE).PHP_EOL;
						
						$response = dspaceMapCollections($itemID, $collectionIDs);
						
						if($response['status'] == 'success')
						{
							$itemReport .= ' -- Mapping successful!'.PHP_EOL;

							$recordTypeCounts['mapped']++;
						}
						else
						{
							$itemReport .= 'FAILURE: '.PHP_EOL.' -- Response received to map request was: '.print_r($response,TRUE).PHP_EOL.' -- Failed to map! '.PHP_EOL.' -- Check the item and map it to the correct collections if needed.';

							$failed[] = $itemID;

							$recordTypeCounts['failed']++;
						}
						
						$itemReport .= $response['status'].PHP_EOL;
					}

					echo $itemReport;

					$report .= $itemReport;
					ob_flush();
				}

				//add unmatched departments to the report
				$report .= 'Unmatched Departments: '.print_r($unmatched,TRUE).PHP_EOL;

				//add failed items to the report
				$report .= 'Failed Items: '.print_r($failed,TRUE).PHP_EOL;
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
?>
