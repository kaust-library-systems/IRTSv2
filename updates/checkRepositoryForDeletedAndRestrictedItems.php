<?php
	//Define function to check for OAI-PMH records in order to identify items that were either deleted or have restricted visibility in DSpace
	function checkRepositoryForDeletedAndRestrictedItems($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$startTime = microtime(TRUE);

		$recordTypeCounts['public'] = 0;
		$recordTypeCounts['embargoed'] = 0;
		$recordTypeCounts['restricted'] = 0;

		if(isset($_GET['handle']))
		{
			$handles = array($_GET['handle']);
		}
		else
		{
			//Get list of handles that have not been checked in last 3 months
			$handles = getValues(
				$irts, 
				"SELECT DISTINCT idInSource FROM metadata r
					WHERE r.source = 'repository' 
					AND NOT EXISTS (
						SELECT idInSource FROM metadata 
							WHERE source = 'repository'
							AND idInSource = r.idInSource
							AND field = 'dspace.record.visibility'
                        	AND added > '".date("Y-m-d H:i:s", strtotime('-3 months'))."'
							AND deleted IS NULL
					)
					AND r.field = 'dspace.type' 
					AND r.value = 'item' 
					AND r.deleted IS NULL
					LIMIT 3000", 
				array('idInSource')
			);
		}

		foreach($handles as $key => $handle)
		{
			$recordTypeCounts['all']++;

			$itemReport = '';
			$oai_status = '';
			$dspace_status = '';
			$dspace_visibility = '';

			$options = array(
				CURLOPT_URL => REPOSITORY_OAI_URL.'verb=GetRecord&metadataPrefix=oai_dc&identifier='.REPOSITORY_OAI_ID_PREFIX.$handle,
				CURLOPT_CUSTOMREQUEST => "GET"
			  );
	  
			$response = makeCurlRequest($options);

			//if responseCode 302, make request to new location
			if($response['responseCode'] == '302')
			{
				$headers = $response['headers'];

				foreach($headers as $header)
				{
					if(strpos($header, 'Location: ') !== FALSE)
					{
						$location = str_replace('Location: ', '', $header);

						$response = makeCurlRequest(array(CURLOPT_URL => $location, CURLOPT_CUSTOMREQUEST => "GET"));
					}
				}
			}
	  
			if($response['status'] == 'success')
			{
				$oai = simplexml_load_string($response['body']);
				
				if(isset($oai->error))
				{
					if((string)$oai->error[0]['code'] === 'idDoesNotExist')
					{
						$oai_status = 'no entry for this handle';

						//Get initial CSRF token and set in session
						$response = dspaceGetStatus();
								
						//Log in
						$response = dspaceLogin();
						
						//check for dspace record by handle
						$response = dspaceGetItemByHandle($handle);

						if($response['status'] == 'success')
						{
							//check UUID
							$dspaceItem = json_decode($response['body'], TRUE);

							if(isset($dspaceItem['uuid']))
							{
								$uuid = $dspaceItem['uuid'];

								//check for resource policies
								$response = dspaceGetResourcePolicies('resource', $uuid);

								if($response['status'] == 'success')
								{
									$policies = json_decode($response['body'], TRUE);

									if(isset($policies['_embedded']['resourcepolicies']))
									{
										$policies = $policies['_embedded']['resourcepolicies'];

										$public = FALSE;
										$embargoed = FALSE;

										foreach($policies as $policy)
										{
											//check for anonymous group policy
											if(isset($policy['_embedded']['group']['name']) && $policy['_embedded']['group']['name'] === 'Anonymous')
											{
												$public = TRUE;

												//check for embargo policy
												if($policy['startDate'] !== NULL && $policy['startDate'] > TODAY)
												{
													$embargoed = TRUE;
												}
											}
										}

										if($embargoed)
										{
											$dspace_visibility = 'embargoed';
										}
										elseif($public)
										{
											$dspace_visibility = 'public';
										}
										else
										{
											$dspace_visibility = 'restricted';
										}
									}
									else
									{
										$dspace_status = 'no resource policies in response';

										$dspace_visibility = 'restricted';
									}
								}
								else
								{
									$dspace_status = 'failed to retrieve resource policies';

									$errors[] = $response;
								}
							}
							else
							{
								$dspace_status = 'no UUID found';
								
								$errors[] = $response;
							}
						}
						else
						{
							//if response code is 404, then the item has been deleted
							if($response['responseCode'] == '404')
							{
								$dspace_status = 'deleted';

								update($irts, 'sourceData', array('deleted'), array(date("Y-m-d H:i:s"), $handle), 'idInSource', ' AND deleted IS NULL');

								update($irts, 'metadata', array('deleted'), array(date("Y-m-d H:i:s"), $handle), 'idInSource', ' AND deleted IS NULL');

								$recordTypeCounts['deleted']++;
							}
							else
							{
								$dspace_status = 'failed to load item';
								
								$errors[] = $response;
							}
						}
					}
				}
				else
				{
					$oai_status = 'record retrieved';

					$dspace_visibility = 'public';
					
					$recordTypeCounts['unchanged']++;
				}
			}
			else
			{
				$oai_status = 'failed to load XML';

				$errors[] = $response;
			}

			//save status of record
			$result = saveValue('oai', $handle, 'oai.status', 0, $oai_status, NULL);

			//save date of status check (so there is a log of checks even when status has not changed)
			$result = saveValue('oai', $handle, 'oai.status.checked', 0, date("Y-m-d H:i:s"), NULL);

			//save visibility of record
			if(!empty($dspace_visibility))
			{
				$recordTypeCounts[$dspace_visibility]++;
				
				$result = saveValue('repository', $handle, 'dspace.record.visibility', 0, $dspace_visibility, NULL);

				//if unchanged, update added timestamp (so that it is not checked again for 3 months)
				if($result['status'] == 'unchanged')
				{
					$result = update($irts, 'metadata', array('added'), array(date("Y-m-d H:i:s"), $result['rowID']), 'rowID');
				}
			}

			if($oai_status !== 'record retrieved')
			{
				$itemReport = $key.") $handle".PHP_EOL;
				$itemReport .= " - OAI Status: $oai_status".PHP_EOL;

				if(!empty($dspace_status))
				{
					$itemReport .= " - DSpace Status: $dspace_status".PHP_EOL;
				}

				if(!empty($dspace_visibility))
				{
					$itemReport .= " - DSpace Visibility: $dspace_visibility".PHP_EOL;
				}
				
				$report .= $itemReport.PHP_EOL;

				echo $itemReport.PHP_EOL;

				ob_flush();
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors, $startTime);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
