<?php
	//Define function to map items to faculty lab collections
	function mapItemsToFacultyLabCollections($report, $errors, $recordTypeCounts)
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

				$recordTypeCounts['skipped'] = 0;

				if(isset($_GET['facultyName'])) // name of PI of lab
				{
					$facultyName = $_GET['facultyName'];

					echo 'Faculty Name: '.$facultyName.PHP_EOL;

					$match = checkPerson(array('name'=>$facultyName));
					//print_r($match);

					if(!empty($match['localID']))
					{
						$facultyLab = checkForFacultyLab($match['localID']);

						$facultyLabCollectionID = $facultyLab['collectionID'];

						echo 'Faculty Lab Collection ID: '.$facultyLabCollectionID.PHP_EOL;

						if(!empty($facultyLabCollectionID))
						{
							$itemIDs = getValues(
								$irts, 
								"SELECT `idInSource` FROM `metadata` 
									WHERE `source` = 'dspace' 
									AND `field` = 'kaust.person'
									AND `value` = '$facultyName'
									AND `deleted` IS NULL
									AND `idInSource` IN (
										SELECT `idInSource` FROM `metadata` 
											WHERE `source` = 'dspace'
											AND `field` = 'dspace.type'
											AND `value` = 'item'
											AND `deleted` IS NULL
											)", 
								array('idInSource'), 
								'arrayOfValues'
							);

							foreach($itemIDs as $itemID)
							{
								$recordTypeCounts['all']++;
								
								echo 'Item ID: '.$itemID.PHP_EOL;

								$response = dspaceMapCollections($itemID, array($facultyLabCollectionID));
								
								if($response['status'] == 'success')
								{
									echo ' - Mapped to: '.$facultyLabCollectionID.PHP_EOL;
								}
								else
								{
									echo 'FAILURE: <br> -- Response received to map request was: '.print_r($response,TRUE).'<br> -- Failed to map to: '.$facultyLabCollectionID.'<br> -- Check the item and map it to the correct collections if needed.';
								}
								
								echo $response['status'].PHP_EOL;

								//try to log in again if failed, normally the tokens just need to be refreshed
								if($response['status'] == 'failed')
								{
									// may need to reauthenticate
									if($response['responseCode'] == '401')
									{
										//Get initial CSRF token and set in session
										$response = dspaceGetStatus();
												
										//Log in
										$response = dspaceLogin();

										$response = dspacePatchMetadata('items', $uuid, $patchJSON);
									
										echo $response['status'].PHP_EOL;

										if($response['status'] == 'failed')
										{
											print_r($response);

											//stop after first failed patch
											//$continuePaging = FALSE;
										}
									}
									else
									{
										print_r($response);
									}
								}
								ob_flush();
							}
						}
					}
					else
					{
						echo 'No match found for faculty name: '.$facultyName.PHP_EOL;
					}
				}
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
?>
