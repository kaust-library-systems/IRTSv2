<?php
	$source = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'irts.source'), array('value'), 'singleValue');
	
	$idInSource = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, TRUE, 'irts.idInSource'), array('value'), 'singleValue');
	
	//prepare flag for if a CC license is found in Crossref or Unpaywall metadata, default to FALSE
	$ccLicense = FALSE;

	//If this is not the Unpaywall review step form, the $doi variable will not be set, but the doi field will exist in the record
	if(isset($record['dc.identifier.doi'][0]))
	{
		$doi = $record['dc.identifier.doi'][0];
	}
	
	if(!empty($doi))
	{
		// check for CC license info from Crossref (there is an error in how we handle crossref records with more than 1 license URL, until we fix that we will need to query multiple forms of the field name)
		$ccLicenseURL = getValues($irts,  "SELECT value FROM `metadata` 
			WHERE `source` = 'crossref' 
			AND `field` IN ('crossref.license.URL','crossref.license.start.URL','crossref.license.start.start.URL') 
			AND (`value` LIKE '%creativecommons.org%' OR `value` LIKE '%ccby%') 
			AND `idInSource` = '$doi' 
			AND deleted IS NULL ", array('value'), 'singleValue');

		if(!empty($ccLicenseURL))
		{
			$ccLicense = TRUE;
			
			$record['dc.rights.uri'][0] = $ccLicenseURL;			
		}
	}
	
	// check for OA links and CC license from Unpaywall
	if(!empty($doi))
	{
		//The Unpaywall response is already set if coming from the Unpaywall review form
		if(isset($unpaywallSourceData))
		{
			$responseJson = $unpaywallSourceData;
			
			//discard any previously set embargo, it will be recalculated based on the current policy
			unset($record['dc.rights.embargodate'][0]);
		}
		else
		{
			$response = queryUnpaywall($doi);
			$responseJson = $response['body'];
		}

		if(is_string($responseJson))
		{
			// convert it to array
			$response = json_decode($responseJson, TRUE);

			// if there is a result
			if(isset($response['best_oa_location'])) {
				if(!is_null($response['best_oa_location']) && !empty($response['best_oa_location']) && !empty($response['oa_locations']) &&  !is_null($response['oa_locations']))
				{
					$record['unpaywall.relation.url'] = array();
					$record['dc.identifier.arxivid'] = array();
					$record['unpaywall.relation.url']['unpaywall.version'] = array();

					//echo 'OA location url:<ul>';
					$oaLocations = $response['oa_locations'];
					foreach ($oaLocations as $oaLocation)
					{
						if(!empty($oaLocation['pmh_id']) && strpos($oaLocation['pmh_id'], 'arXiv') !== false)
						{
							// Example of unpaywall arxiv id : oai:arXiv.org:1803.04951
							
							$record['dc.identifier.arxivid'][] = substr($oaLocation['pmh_id'], (strripos( $oaLocation['pmh_id'],':') + 1 ), strlen($oaLocation['pmh_id']));
						}
						
						//if there was no CC license in the Crossref metadata, check if there is one in the Unpaywall metadata
						if(!$ccLicense)
						{
							if(!empty($oaLocation['license']) && strpos($oaLocation['license'], 'cc-by') !== false && $oaLocation['version'] === 'publishedVersion' && $oaLocation['host_type'] === 'publisher')
							{
								$ccLicenseURL = str_replace('cc-', 'https://creativecommons.org/licenses/', $oaLocation['license']).'/4.0/';
								
								$ccLicense = TRUE;
								
								$record['dc.rights.uri'][0] = $ccLicenseURL;			
							}
						}

						array_push($record['unpaywall.relation.url'], $oaLocation['url']);
						array_push($record['unpaywall.relation.url']['unpaywall.version'], $oaLocation['version']);
					}
				}			
			}			
		}
	}
		
	if($ccLicense)
	{
		echo '<div class="col-sm-12 alert-warning border border-dark rounded"><b> NOTE: </b>The Version, Terms of Use, and Link to License have been set based on the existence of a Creative Commons license in the Crossref record for this DOI. Please check that this information corresponds correctly with the actual article information on the publisher site. For example, if the file currently available from the publisher website is the Accepted Manuscript version, the Version information should be changed to "Post-print".</div><hr>';
		
		if($record['dc.type'][0] === 'Preprint') //some techArxiv preprints have Creative Commons licenses assigned in Crossref
		{
			$record['dc.eprint.version'][0] = "Pre-print";
			
			$record['dc.rights'][0] = 'This is a preprint version of a paper and has not been peer reviewed. Archived with thanks to '.$record['dc.publisher'][0].' under a Creative Commons license, details at: '.$ccLicenseURL;
		}
		else
		{
			$record['dc.eprint.version'][0] = "Publisher's Version/PDF";
			
			if(!empty($record['dc.identifier.journal'][0]))
			{
				$record['dc.rights'][0] = 'Archived with thanks to '.$record['dc.identifier.journal'][0].' under a Creative Commons license, details at: '.$ccLicenseURL;
			}
			elseif(!empty($record['dc.publisher'][0]))
			{
				$record['dc.rights'][0] = 'Archived with thanks to '.$record['dc.publisher'][0].' under a Creative Commons license, details at: '.$ccLicenseURL;
			}
			else
			{
				$record['dc.rights'][0] = 'Archived under a Creative Commons license, details at: '.$ccLicenseURL;
			}
		}
	}
	elseif($record['dc.type'][0] === 'Preprint') //no need to check Sherpa Romeo for preprint servers that have an ISSN (this applies only really to SSRN which is listed as SSRN Electronic Journal, but is not a journal)
	{
		//Set version
		$record['dc.eprint.version'][0] = "Pre-print";
	}
	else //Insert Sherpa Romeo policy information if available
	{
		//Default version is accepted manuscript
		$record['dc.eprint.version'][0] = "Post-print";		
		
		//First, get ISSN if available
		$issn = $record['dc.identifier.issn'][0];
		
		if(empty($issn))
		{
			$issn = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.identifier.issn'), array('value'), 'singleValue');

			if(empty($issn)&&$source!=='crossref')
			{
				if(!empty($doi))
				{
					$issn = getValues($irts, setSourceMetadataQuery('crossref', $doi, NULL, 'dc.identifier.issn'), array('value'), 'singleValue');
				}
			}
		}

		if(!empty($issn))
		{
			$romeoResults = querySherpaRomeo('publication', array('field'=>'issn', 'operator'=>'equals', 'value'=>$issn));

			if(empty($romeoResults['items']))
			{
				if($record['dc.publisher'][0] === 'IEEE')
				{
					$publisher = 'Institute of Electrical and Electronics Engineers';

					$romeoResults = querySherpaRomeo('publisher', array('field'=>'name', 'operator'=>'equals', 'value'=>$publisher));
				}
			}

			if(empty($romeoResults['items']))
			{
				$romeoResults = querySherpaRomeo('publisher', array('field'=>'name', 'operator'=>'equals', 'value'=>$record['dc.publisher'][0]));
			}

			if(count($romeoResults['items'])===0)
			{
				echo '<div class="col-sm-12 alert-warning border border-dark rounded">No Sherpa Romeo results returned, please search Sherpa Romeo directly at: <a href="https://v2.sherpa.ac.uk/romeo/search.html" target="_blank">https://v2.sherpa.ac.uk/romeo/search.html</a>, or check the journal or publisher website directly to find the relevant policy.</div>';
			}
			else
			{
				$policyLink = $romeoResults['items'][0]["system_metadata"]["uri"];

				if(isset($romeoResults['items'][0]['publisher_policy']))
				{
					$policies = $romeoResults['items'][0]['publisher_policy'];
				}
				elseif(isset($romeoResults['items'][0]['policies']))
				{
					//Assume that first listed publisher policy listed is the default policy
					$policies[] = $romeoResults['items'][0]['policies'][0];
				}

				if(!isset($policies))
				{
					echo '<div class="col-sm-12 alert-warning border border-dark rounded">No publisher policy listed in the Sherpa Romeo results, please search Sherpa Romeo directly at: <a href="https://v2.sherpa.ac.uk/romeo/search.html" target="_blank">https://v2.sherpa.ac.uk/romeo/search.html</a>, or check the journal or publisher website directly to find the relevant policy.</div>';
				}
				else
				{
					$acceptableLocations = array("any_repository","institutional_repository","institutional_website","non_commercial_repository","non_commercial_institutional_repository");

					$policiesFound = array();
					
					foreach($policies as $policyGroup)
					{
						foreach($policyGroup['permitted_oa'] as $policy)
						{
							//print_r($policy);
							$desiredVersions = array('published','accepted');

							if(isset($policy['article_version'][0]))
							{
								if(in_array($policy['article_version'][0], $desiredVersions))
								{
									foreach($policy['location']['location'] as $location)
									{
										//echo $location;
										if(in_array($location,$acceptableLocations))
										{
											if($policy['additional_oa_fee']==='no')
											{
												//print_r($policy);
												
												//if there are no prerequisites, or if the prerequisite is that deposit be required by the institution, then the policy applies to us
												if(!isset($policy['prerequisites']))
												{
													$policiesFound[$policy['article_version'][0]][] = $policy;
												}
												elseif(isset($policy['prerequisites']['prerequisites']))
												{
													if(in_array('when_required_by_institution', $policy['prerequisites']['prerequisites']))
													{
														$policiesFound[$policy['article_version'][0]][] = $policy;
													}
												}
											}
										}
									}
								}
							}
						}
					}

					$policySelected = array();

					//Preference is for published version if policy allows
					if(isset($policiesFound['published']))
					{
						$record['dc.eprint.version'][0] = "Publisher's Version/PDF";
						$policySelected = $policiesFound['published'][0];
					}
					elseif(isset($policiesFound['accepted']))
					{
						$record['dc.eprint.version'][0] = "Post-print";
						$policySelected = $policiesFound['accepted'][0];
					}

					if(!empty($policySelected))
					{
						echo '<b>Relevant policy details:</b><br>';

						$relevantFields = array("article_version","copyright_owner","additional_oa_fee","location","embargo","license");

						//,"conditions","prerequisites","public_notes"

						ksort($policySelected);

						foreach($policySelected as $policyField => $policyValue)
						{
							if(in_array($policyField, $relevantFields))
							{
								echo "<br>".ucfirst(str_replace('_', ' ', $policyField)).": ";

								if(is_array($policyValue))
								{
									if($policyField === 'embargo')
									{
										echo $policyValue['amount'].' '.$policyValue['units'];
									}
									elseif($policyField === 'license')
									{
										echo $policyValue[0]['license_phrases'][0]['phrase'];
									}
									elseif($policyField === 'location')
									{
										echo '<ul>';
										foreach($policyValue['location_phrases'] as $location)
										{
											echo '<li>'.$location['phrase'].'</li>';
										}
										echo '</ul>';
									}
									else
									{
										echo $policyValue[0];
									}
								}
								else
								{
									echo $policyValue;
								}
							}
						}

						if(isset($policySelected['embargo']) )
						{
							$record['dc.rights.embargolength'][0] = $policySelected['embargo']['amount'];
							
							$pubDate = $record['dc.date.issued'][0];
							
							//Conversion to DateTime requires YYYY-MM-DD format
							if(strlen($pubDate)===4)
							{
								$pubDate = $pubDate.'-01-01';
							}
							elseif(strlen($pubDate)===7)
							{
								$pubDate = $pubDate.'-01';
							}
							
							$date = new DateTime($pubDate);
							$date->add(new DateInterval('P'.$record['dc.rights.embargolength'][0].'M'));
							$record['dc.rights.embargodate'][0] = $date->format('Y-m-d');
						}

						if(strpos($record['dc.publisher'][0], 'Elsevier') !==FALSE)
						{
							$result = retrieveScienceDirectArticleHostingPermissionsByDOI($doi);

							if(!empty($result['embargo']))
							{
								$record['dc.rights.embargodate'][0] = $result['embargo'];
							}
						}
					}

					echo '<br>Full policy record in Sherpa Romeo at: <a href="'.$policyLink.'" target="_blank">'.$policyLink.'</a>.<br><hr>
					<div class="col-sm-12 alert-warning border border-dark rounded"><b>NOTE:</b> Please check if a separate license (such as a CC license) has been applied at the article level. If so, that license should be used in place of any publisher or journal default policies.</div><hr>';
				}
			}
		}
		
		//set publisher set statement
		if(isset($doi))
		{
			$publisher = getValues($irts, setSourceMetadataQuery('crossref', $doi, NULL, 'dc.publisher'), array('value'), 'singleValue');

			//if matched in crossref table
			if(!empty($publisher))
			{
				if(empty($record['dc.publisher'][0]))
				{
					$record['dc.publisher'][0] = $publisher;
				}
				//echo $publisher;

				$publisherID = getValues($irts, setSourceMetadataQuery('sherpaRomeo', NULL, NULL, 'crossref.publisher.name', $publisher), array('idInSource'), 'singleValue');

				if(!empty($publisherID))
				{
					//echo $publisherID;

					$setStatement = getValues($irts, setSourceMetadataQuery('sherpaRomeo', $publisherID, NULL, 'irts.publisher.setStatement'), array('value'), 'singleValue');

					if(!empty($setStatement))
					{
						//echo $setStatement;

						$placeHolders = array('[JournalTitle]'=>'dc.identifier.journal','[DOI]'=>'dc.identifier.doi','[ArticleLink]'=>'dc.relation.url','[pubDate]'=>'dc.date.issued','[Volume]'=>'dc.identifier.volume','[Issue]'=>'dc.identifier.issue');

						foreach($placeHolders as $placeHolder=>$field)
						{
							if(isset($record[$field][0]))
							{
								$setStatement = str_replace($placeHolder, $record[$field][0], $setStatement);
							}
						}

						$setStatement = str_replace('[year]', substr($record['dc.date.issued'][0], 0, 4), $setStatement);

						$record['dc.rights'][0] = $setStatement;
					}
				}
			}
		}
	}
	
	//Set default rights statement if no publisher set statement set
	if(empty($record['dc.rights'][0]))
	{
		if($record['dc.eprint.version'][0] === "Post-print")
		{
			$record['dc.rights'][0] = "This is an accepted manuscript version of a paper before final publisher editing and formatting.";
			
			if(!empty($record['dc.publisher'][0]))
			{
				$record['dc.rights'][0] .= ' Archived with thanks to '.$record['dc.publisher'][0].'.';
			}
			
			if(!empty($record['dc.identifier.journal'][0]))
			{
				$record['dc.rights'][0] .= ' The version of record is available from '.$record['dc.identifier.journal'][0].'.';
			}
		}
		
		if($record['dc.eprint.version'][0] === "Pre-print")
		{
			$record['dc.rights'][0] = "This is a preprint version of a paper and has not been peer reviewed.";
			
			if(isset($record['dc.identifier.journal'][0]) && $record['dc.identifier.journal'][0] === 'SSRN Electronic Journal')
			{
				$record['dc.rights'][0] .= ' Archived with thanks to SSRN.';
			} 
			elseif(!empty($record['dc.publisher'][0]))
			{
				$record['dc.rights'][0] .= ' Archived with thanks to '.$record['dc.publisher'][0].'.';
			}
		}
	}
?>
