<?php
	//Define function to manage the simple item display for repository records
	function manageRepositoryItemDiplay($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{			
			if(isset($_GET['handle']))
			{
				$handles = [$_GET['handle']];
			}
			else
			{
				/* $query = "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.date.accessioned' 
				AND `value` < '2023-10-30'
				AND idInSource IN (
					SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.type' 
					AND `value` NOT IN ('Dissertation','Poster','Thesis','Image','Specimen','Observation Record','Species Summary')
					)";  */
				
				/* $query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.date.accessioned' 
				AND `value` < '2023-10-30'
				AND deleted IS NULL
				AND idInSource IN (
					SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.type' 
					AND `value` IN ('Article','Book Chapter','Conference Paper','Preprint','Presentation')
					AND deleted IS NULL
					)"; */
				
				$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.date.accessioned' 
				AND `value` < '2023-10-30'
				AND deleted IS NULL
				AND idInSource IN (
					SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.type' 
					AND `value` IN ('Article','Book Chapter','Conference Paper','Preprint','Presentation')
					AND deleted IS NULL
					)
                AND idInSource NOT IN (
					SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dspace.lastModified' 
					AND deleted IS NULL
					)";
	
				$handles = getValues($irts, $query, array('idInSource'));
			}			
						
			foreach($handles as $handle)
			{
				$response = dspaceGetItemByHandle($handle);

				if($response['status'] == 'success')
				{
					$item = json_decode($response['body'], TRUE);

					$uuid = $item['uuid'];

					echo $uuid.PHP_EOL;

					//if bearer token has expired, metadata will not have hidden fields, reauthentication is needed
					if(!isset($item['metadata']['dc.description.provenance']))
					{
						//Get initial CSRF token and set in session
						$response = dspaceGetStatus();
															
						//Log in
						$response = dspaceLogin();

						$response = dspaceGetItemByHandle($handle);

						if($response['status'] == 'success')
						{
							$item = json_decode($response['body'], TRUE);

							//if bearer token has expired, response will not have hidden fields, 
							if(!isset($item['metadata']['dc.description.provenance']))
							{
								continue;
							}
						}
					}
					
					if(isset($item['metadata']['dc.identifier.doi']))
					{
						$doi = $item['metadata']['dc.identifier.doi'][0]['value'];

						if(!empty($doi))
						{
							$idInIRTS = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'), 'singleValue');
						}
					}

					$kaustPersons = [];
					if(isset($item['metadata']['kaust.person']))
					{
						foreach($item['metadata']['kaust.person'] as $kaustPerson)
						{
							$kaustPersons[] = $kaustPerson['value'];
						}
					}

					$metadata = [];

					$orcidAuthors = [];

					foreach($item['metadata'] as $field => $entries)
					{
						foreach($entries as $place => $entry)
						{
							$orcid = '';
							
							$newEntry = [];

							$newEntry['value'] = $entry['value'];

							if(!empty($entry['authority']))
							{
								$authority = $entry['authority'];
								
								$orcid = getValues($irts, "SELECT m2.`value` FROM `metadata` m
									LEFT JOIN metadata m2 ON m.parentRowID = m2.parentRowID
									WHERE m.`source` LIKE 'repository'
									AND m.`field` LIKE 'dspace.authority.key'
									AND m.value LIKE '$authority'
									AND m.deleted IS NULL
									AND m2.`source` LIKE 'repository'
									AND m2.`field` LIKE 'dc.identifier.orcid'
									AND m2.deleted IS NULL", array('value'), 'singleValue');
							}

							if($field == 'dc.contributor.author')
							{
								//check in IRTS for ORCID
								if(empty($orcid) && !empty($idInIRTS))
								{
									$orcid = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$entry['value']), 'dc.identifier.orcid'), array('value'), 'singleValue');
								}

								if(empty($orcid) && !empty($kaustPersons))
								{
									//local person handling
									if(in_array($entry['value'], $kaustPersons))
									{
										$match = checkPerson(array('name'=>$entry['value']));
										//print_r($match);

										if(!empty($match['orcid']))
										{
											$orcid = $match['orcid'];
										}
									}
								}
								
								if(!empty($orcid))
								{
									$orcidAuthors[$place] = $entry['value'] . '::' . $orcid;
								}
								else
								{
									//keep author names even when there is no ORCID so that place stays consistent with dc.contributor.author
									$orcidAuthors[$place] = $entry['value'];
								}
							}

							if(!empty($orcid))
							{
								$newEntry['children']['dc.identifier.orcid'][]['value'] = $orcid;
							}

							$metadata[$field][] = $newEntry;
						}
					}

					$patches = [];

					//remove dc.identifier.orcid field, this will only be used during submission for ETDs
					if(isset($metadata['dc.identifier.orcid']))
					{
						unset($metadata['dc.identifier.orcid']);
						
						$patches[] = array("op" => "remove",
									"path" => "/metadata/dc.identifier.orcid");
					}

					if(isset($metadata['orcid.author']))
					{
						unset($metadata['orcid.author']);
						
						$patches[] = array("op" => "remove",
									"path" => "/metadata/orcid.author");
					}
					
					$result = setDisplayFields($metadata);
					
					$patches = array_merge($patches, $result['patch']);

					if(!empty($orcidAuthors))
					{
						foreach($orcidAuthors as $place => $orcidAuthor)
						{
							$patches[] = array("op" => "add",
							"path" => "/metadata/orcid.author/-",
							"value" => array("value" => $orcidAuthor));
						}
					}

					$patchJSON = json_encode($patches);
					
					$response = dspacePatchMetadata('items', $uuid, $patchJSON);
					
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

							print_r($patchJSON);
						}
					}

					set_time_limit(0);
					ob_flush();
				}						
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
