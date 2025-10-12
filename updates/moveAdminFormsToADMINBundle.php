<?php
	//Define function to move admin forms to the ADMIN bundle
	function moveAdminFormsToADMINBundle($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$source = 'repository';

		$recordTypeCounts['adminFilesIdentified'] = 0;		
		$recordTypeCounts['hasSupplementalFiles'] = 0;
		$recordTypeCounts['adminFilesMoved'] = 0;
		$recordTypeCounts['resourcePoliciesDeleted'] = 0;
		$recordTypeCounts['derivativeFilesDeleted'] = 0;
		$recordTypeCounts['noNonAdminFileIdentified'] = 0;

		//accept handle as GET parameter
		if(isset($_GET['handle']))
		{
			$handles = [$_GET['handle']];
		}
		else
		{
			//get handles of theses and dissertations without an ADMIN bundle
			$handles = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` 
				WHERE source = '$source'
				AND field = 'dspace.community.handle'
				AND value = '10754/124545'", array('idInSource'), 'arrayOfValues');
		}

		//loop through handles
		foreach($handles as $handle)
		{
			//Get initial CSRF token and set in session
			$response = dspaceGetStatus();
					
			//Log in
			$response = dspaceLogin();
			
			$recordTypeCounts['all']++;
			
			$itemReport = PHP_EOL.$recordTypeCounts['all'].') Handle: '.$handle.PHP_EOL;
			
			//get the item UUID
			$itemUUID = getValues($irts, "SELECT `value` FROM `metadata` 
				WHERE source = '$source' 
				AND idInSource = '$handle' 
				AND field = 'dspace.uuid'", array('value'), 'singleValue');

			$itemReport .= '- Item UUID: '.$itemUUID.PHP_EOL;
			
			//get the bundles
			$response = dspaceListItemBundles($itemUUID);

			usleep(100);

			if($response['status'] == 'success')
			{
				$bundles = json_decode($response['body'], TRUE);

				$bundles = $bundles['_embedded']['bundles'];

				$derivativeBundles = [];

				$adminBundleUUID = '';

				$adminFiles = [];

				$movedAdminFileNames = [];

				$nonAdminFileExists = FALSE;

				$countOfNonAdminFiles = 0;

				foreach($bundles as $bundle)
				{
					if($bundle['name'] == 'ORIGINAL')
					{
						$originalBundleUUID = $bundle['uuid'];

						$itemReport .= '- ORIGINAL Bundle UUID: '.$originalBundleUUID.PHP_EOL;

						$response = dspaceListBundlesBitstreams($originalBundleUUID);

						usleep(100);

						if($response['status'] == 'success')
						{
							$originalBundle = json_decode($response['body'], TRUE);

							$originalBundleBitstreams = $originalBundle['_embedded']['bitstreams'];

							foreach($originalBundleBitstreams as $key => $bitstream)
							{
								$itemReport .= '-- File '.$key.') '.$bitstream['name'].' ( '.$bitstream['uuid'].' )'.PHP_EOL;
								
								$response = dspaceResourcePolicies($bitstream['uuid']);

								usleep(100);

								if($response['status'] == 'success')
								{
									$resourcePolicies = json_decode($response['body'], TRUE);

									$countOfResourcePolicies = $resourcePolicies['page']['totalElements'];

									$itemReport .= '--- Resource Policies: '.$countOfResourcePolicies.PHP_EOL;

									if($countOfResourcePolicies == 0)
									{
										$itemReport .= '---- File has no resource policies'.PHP_EOL;
										
										$adminFiles[$bitstream['name']] = $bitstream['uuid'];

										$itemReport .= '----> File is an ADMIN form'.PHP_EOL;
									}
									else
									{
										$hasAnonymousPolicy = FALSE;

										foreach($resourcePolicies['_embedded']['resourcepolicies'] as $resourcePolicy)
										{
											//non admin files will have an anonymous group policy
											if(isset($resourcePolicy['_embedded']['group']['name']) && $resourcePolicy['_embedded']['group']['name'] == 'Anonymous')
											{
												$hasAnonymousPolicy = TRUE;
												break;
											}

											//old non admin files may have a KAUST_Thesis_Reader group policy that functions as an Anonymous policy
											if(isset($resourcePolicy['_embedded']['group']['name']) && $resourcePolicy['_embedded']['group']['name'] == 'KAUST_Thesis_Reader')
											{
												$hasAnonymousPolicy = TRUE;
												break;
											}

											//old files may have a KAUST_Thesis_Format_Checkers group policy that has expired and can be removed
											if(isset($resourcePolicy['_embedded']['group']['name']) && $resourcePolicy['_embedded']['group']['name'] == 'KAUST_Thesis_Format_Checkers')
											{
												$response = dspaceDeleteResourcePolicy($resourcePolicy['id']);

												usleep(100);

												if($response['status'] == 'success')
												{
													$recordTypeCounts['resourcePoliciesDeleted']++;
													
													$itemReport .= '---- KAUST_Thesis_Format_Checkers group Resource Policy '.$resourcePolicy['id'].' deleted'.PHP_EOL;
												}
												else
												{
													$itemReport .= 'Error: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
												}
											}
										}

										//if no anonymous policy, it is an admin form
										if(!$hasAnonymousPolicy)
										{
											$itemReport .= '---- File has no Anonymous group policy'.PHP_EOL;
											
											$adminFiles[$bitstream['name']] = $bitstream['uuid'];

											$itemReport .= '----> File is an ADMIN form'.PHP_EOL;
										}
										else //check if bitstream name indicates that it is a form
										{
											$stringsToMatch = ['advisor', 'approval', 'copyright', 'availability', 'results', 'license', 'permissions'];
											foreach($stringsToMatch AS $string)
											{
												if(stripos($bitstream['name'], $string) !== FALSE)
												{
													$itemReport .= '---- File name contains "'.$string.'"'.PHP_EOL;

													$itemReport .= '----> File is an ADMIN form'.PHP_EOL;
													
													$adminFiles[$bitstream['name']] = $bitstream['uuid'];

													$response = dspaceResourcePolicies($bitstream['uuid']);

													usleep(100);

													if($response['status'] == 'success')
													{
														$resourcePolicies = json_decode($response['body'], TRUE);
														
														//admin forms should not have Anonymous or other resource policies that allow public access and Admin users can still access them without a resource policy, so all resource policies can be removed
														foreach($resourcePolicies['_embedded']['resourcepolicies'] as $resourcePolicy)
														{
															$response = dspaceDeleteResourcePolicy($resourcePolicy['id']);

															usleep(100);

															if($response['status'] == 'success')
															{
																$recordTypeCounts['resourcePoliciesDeleted']++;
																
																$itemReport .= '-----> resource policy '.$resourcePolicy['id'].' deleted'.PHP_EOL;
															}
															else
															{
																$itemReport .= 'Error: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
															}
														}
													}
												}
											}
										}
									}

									if(!isset($adminFiles[$bitstream['name']]))
									{
										$nonAdminFileExists = TRUE;

										$countOfNonAdminFiles++;
										
										$itemReport .= '----> File is NOT an ADMIN form'.PHP_EOL;
									}
								}
							}

							if($countOfNonAdminFiles > 1)
							{
								$itemReport .= '----- Record has '.$countOfNonAdminFiles.' non admin files, the additional files should be supplemental files'.PHP_EOL;
								
								$recordTypeCounts['hasSupplementalFiles']++;
							}
						}
						else
						{
							$itemReport .= 'Error Retrieving ORIGINAL Bundle Bitstreams: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
						}
					}
					elseif($bundle['name'] == 'ADMIN')
					{
						$adminBundleUUID = $bundle['uuid'];
					}
					elseif(in_array($bundle['name'], ['TEXT','THUMBNAIL']))
					{
						$derivativeBundles[$bundle['name']] = $bundle['uuid'];
					}
					//remove tiles bundles
					elseif(strpos($bundle['name'], 'tiles_') !== FALSE)
					{
						$response = dspaceDeleteBundle($bundle['uuid']);

						usleep(100);

						if($response['status'] == 'success')
						{
							$itemReport .= '-- Bundle '.$bundle['name'].' ( '.$bundle['uuid'].' ) deleted'.PHP_EOL;
						}
						else
						{
							$itemReport .= 'Error: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
						}
					}
				}

				//Create ADMIN bundle if it does not exist
				if(empty($adminBundleUUID))
				{
					$bundle = '{"name":"ADMIN"}';

					$response = dspaceCreateItemBundle($itemUUID, $bundle);

					usleep(100);

					if($response['status'] == 'success')
					{
						$adminBundleUUID = json_decode($response['body'], TRUE)['uuid'];
					}
					else
					{
						$itemReport .= 'Error Creating ADMIN Bundle: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
					}
				}

				if(!empty($adminBundleUUID) && $nonAdminFileExists)
				{
					//Move admin files to ADMIN bundle
					foreach($adminFiles as $adminFileName => $adminFileUUID)
					{
						$recordTypeCounts['adminFilesIdentified']++;
						
						$response = dspaceMoveBitstream($adminFileUUID, $adminBundleUUID);

						usleep(100);

						if($response['status'] == 'success')
						{
							$recordTypeCounts['adminFilesMoved']++;
							
							$itemReport .= '- File '.$adminFileUUID.' ( '.$adminFileName.' ) moved to ADMIN bundle'.PHP_EOL;

							$movedAdminFileNames[] = $adminFileName;
						}
						else
						{
							$itemReport .= 'Error: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
						}
					}

					//Delete ADMIN form derivative files
					foreach($derivativeBundles as $derivativeBundleName => $derivativeBundleUUID)
					{
						$response = dspaceListBundlesBitstreams($derivativeBundleUUID);

						usleep(100);

						if($response['status'] == 'success')
						{
							$derivativeBundle = json_decode($response['body'], TRUE);

							$derivativeBundleBitstreams = $derivativeBundle['_embedded']['bitstreams'];

							foreach($derivativeBundleBitstreams as $derivativeBitstream)
							{
								$sourceFileName = str_replace(['.jpg','.txt'], '', $derivativeBitstream['name']);

								if(in_array($sourceFileName, $movedAdminFileNames))
								{
									$response = dspaceDeleteBitstream($derivativeBitstream['uuid']);

									usleep(100);

									if($response['status'] == 'success')
									{
										$recordTypeCounts['derivativeFilesDeleted']++;
										
										$itemReport .= '--- Derived file '.$derivativeBitstream['name'].' ( '.$derivativeBitstream['uuid'].' ) deleted'.PHP_EOL;
									}
									else
									{
										$itemReport .= 'Error: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
									}
								}
							}
						}
						else
						{
							$itemReport .= 'Error Retrieving '.$derivativeBundleName.' Bundle Bitstreams: '.PHP_EOL.print_r($response, TRUE).PHP_EOL;
						}
					}
				}
				else
				{
					$itemReport .= '----- Error: all files identified as admin files, no action taken.......'.PHP_EOL;

					$recordTypeCounts['noNonAdminFileIdentified']++;
				}
			}
			$report .= $itemReport;			
			echo $itemReport;
			set_time_limit(0);
			ob_flush();
			usleep(100);
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
