<?php
	//Define function to check ORCID for new publications
	function harvestOrcid($source)
	{
		global $irts, $newInProcess, $errors;

		$sourceReport = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0);
		
		$dois = array();

		//Harvest for direct query of ORCID by ORCIDs
		// get all the ORCID ids 
		$orcids = getValues($ioi, "SELECT `orcid` FROM `orcids`", array('orcid'), 'arrayOfValues');
		// $orcids = array('0000-0001-5435-4750');

		foreach ($orcids as $orcid)
		{
			$opts = array(
			  'http'=>array(
				'method'=>"GET",
				'header'=>"Accept: application/json"
			  )
			);

			$context = stream_context_create($opts);
			
			//get works list from ORCID
			$output = json_decode(file_get_contents(ORCID_API_URL.$orcid.'/works', false, $context), true);
			
			//print_r($output);
			
			if(isset($output['group']))
			{				
				//print_r($output['group']);
				
				//for each Putcode check it the putcode already in our repository 
				foreach ($output['group'] as $work) 
				{
					//print_r($work);
					
					$handle = '';
					foreach ($work['external-ids']['external-id'] as $externalid)
					{
						if($externalid['external-id-type'] === "handle")
						{
							if(strpos($externalid['external-id-value'],'10754/')!==FALSE)
							{
								$handle = $externalid['external-id-value'];
								
								//echo $handle.PHP_EOL;
								break;
							}
						}
					}					
					
					if(empty($handle))
					{
						foreach ($work['work-summary'] as $workSummary)
						{
							if($workSummary['publication-date']['year']['value'] > 2009)
							{
								foreach ($workSummary['external-ids']['external-id'] as $externalid)
								{
									if($externalid['external-id-type'] === "doi")
									{
										//echo $externalid['external-id-value'].PHP_EOL;
										
										$dois[] = $externalid['external-id-value'];
									}
								}
							}
						}
					}
				}
			}
		}

		$dois = array_unique($dois);
		
		//print_r($dois);
		
		//$dois = array_slice($dois, 0, 10);

		foreach($dois as $key => $doi)
		{
			$sourceReport .= $key.') DOI: '.$doi.PHP_EOL;
			
			echo $key.') DOI: '.$doi.PHP_EOL;
			
			$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $report);
	
			if(!empty($existingRecords))
			{
				echo ' -- Existing Handles: '.implode('; ', $existingRecords).PHP_EOL;
			}
			else
			{
				$irtsIDs = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'));
				
				if(!empty($irtsIDs))
				{
					echo ' -- Existing IRTS IDs: '.implode('; ', $irtsIDs).PHP_EOL;
					
					foreach($irtsIDs as $irtsID)
					{
						$status = getValues($irts, setSourceMetadataQuery('irts', $irtsID, NULL, 'irts.status'), array('value'), 'singleValue');
						
						echo " -- $irtsID status: $status".PHP_EOL;
					}
				}
				else
				{
					if(identifyRegistrationAgencyForDOI($doi, $sourceReport)==='crossref')
					{
						$recordTypeCounts['all']++;

						$sourceData = retrieveCrossrefMetadataByDOI($doi, $sourceReport);

						if(!empty($sourceData))
						{
							$recordType = processCrossrefRecord($sourceData, $sourceReport);

							$sourceReport .= ' - '.$recordType.PHP_EOL;

							$recordTypeCounts[$recordType]++;					

							$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $sourceReport);

							if(empty($existingRecords))
							{
								//Check for existing IRTS entry
								$irtsID = 'crossref_'.$doi;

								$query = "SELECT `idInSource` FROM `metadata` WHERE source LIKE 'irts' AND (idInSource LIKE '$irtsID' OR (field = 'dc.identifier.doi' AND value = '$doi'))";

								$check = $irts->query($query);

								if($check->num_rows === 0)
								{
									$field = 'dc.type';

									$type = getValues($irts, setSourceMetadataQuery('crossref', $doi, NULL, $field), array('value'), 'singleValue');

									$rowID = mapTransformSave('irts', $irtsID, '', $field, '', 1, $type, NULL);

									$field = 'status';

									$rowID = mapTransformSave('irts', $irtsID, '', $field, '', 1, 'inProcess', NULL);

									$field = 'dc.identifier.doi';

									$rowID = mapTransformSave('irts', $irtsID, '', $field, '', 1, $doi, NULL);

									$newInProcess++;
									
									echo " -- New IRTS ID: $irtsID".PHP_EOL;
								}
							}
						}
					}
				}
			}
		}

		$sourceSummary = saveReport($source, $sourceReport, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
	
?>
