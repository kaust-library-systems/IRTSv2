<?php
	//Define function to check for dataset DOIs, Bioproject IDs and GitHub URLs in data availability statements that were not added during manual processing
	function checkForRelatedIdentifiers($report, $errors, $recordTypeCounts)
	{
		global $irts;
				
		/* $githubURLs = getValues($irts, "SELECT value FROM `metadata` 
		WHERE `source` LIKE 'irts'
		AND `field` = 'dc.related.codeURL' 
		AND `value` LIKE '%github.com%' 
		AND `deleted` IS NULL", array('value'), 'arrayOfValues'); */
	
		$abstracts =  getValues($irts, "SELECT source, idInSource, value FROM `metadata`
		WHERE `source` IN ('irts','repository')
		AND `field` LIKE 'dc.description.abstract'
		AND `value` LIKE '%github.com%'
		AND deleted IS NULL", array('source', 'idInSource', 'value'), 'arrayOfValues');
		
		foreach($abstracts as $abstract)
		{
			print_r($abstract);
			
			if($abstract['source'] === 'irts')
			{
				$idInIRTS = $abstract['idInSource'];
			}
			else
			{
				$idInIRTS =  getValues($irts, "SELECT idInSource FROM `metadata`
					WHERE `source` LIKE 'irts'
					AND `field` IN ('dc.identifier.doi','dc.identifier.arxivid')
					AND `value` IN (
						SELECT value FROM metadata WHERE `source` LIKE 'repository' AND `idInSource` LIKE '".$abstract['idInSource']."' AND `field` IN ('dc.identifier.doi','dc.identifier.arxivid') AND deleted IS NULL
					)
					AND deleted IS NULL", array('idInSource'), 'singleValue');
			}
			
			if(!empty($idInIRTS))
			{
				preg_match_all('/https:\/\/github.com\/[-a-zA-Z0-9@:%_\+~\#?&\/\/=]*/', $abstract['value'], $githubURLsGroups);
				
				foreach($githubURLsGroups as $githubURLsGroup)
				{
					foreach($githubURLsGroup as $key => $githubURL)
					{
						echo $githubURL.PHP_EOL;
						
						$recordTypeCounts['all']++;
						
						$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
						WHERE `source` LIKE 'irts' 
						AND `idInSource` LIKE '$idInIRTS' 
						AND (
								`field` IN ('dc.relation.issupplementedby','dc.relation.references') 
								AND value LIKE 'URL:$githubURL%' 
								AND `deleted` IS NULL
							)
						OR	
							(
								`field` LIKE 'dc.related.codeURL' 
								AND value LIKE '$githubURL%' 
							)", array('idInSource'));
						
						if(empty($existing))
						{
							$place = getValues($irts, "SELECT place FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '$idInIRTS'
							AND `field` LIKE 'dc.related.codeURL'
							ORDER BY place DESC
							LIMIT 1", array('place'), 'singleValue');
							
							if(empty($place))
							{
								$place = 1;
							}
							else
							{
								$place = $place+1;
							}
							
							$result = saveValue('irts', $idInIRTS, 'dc.related.codeURL', $place, $githubURL, NULL);
							
							$recordTypeCounts['new']++;
						}
						else
						{
							$recordTypeCounts['unchanged']++;
						}
					}
				}
				
				preg_match_all('/\.[^.]+github\.com.+$/', $abstract['value'], $sentenceGroups);
				
				foreach($sentenceGroups as $sentenceGroup)
				{
					foreach($sentenceGroup as $sentence)
					{
						$sentence = trim(substr($sentence, 1));
						
						echo $sentence.PHP_EOL;
						
						$recordTypeCounts['all']++;
						
						$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
						WHERE `source` LIKE 'irts' 
						AND `idInSource` LIKE '$idInIRTS' 
						AND `field` LIKE 'dc.description.dataAvailability' 
						AND `deleted` IS NULL", array('idInSource'));
						
						if(empty($existing))
						{
							$result = saveValue('irts', $idInIRTS, 'dc.description.dataAvailability', 1, $sentence, NULL);
							
							$recordTypeCounts['new']++;
						}
						else
						{
							$recordTypeCounts['unchanged']++;
						}
					}
				}
			}
			else
			{
				echo 'idInIRTS not found'.PHP_EOL;
				
				$recordTypeCounts['skipped']++;
			}
		}
		
		//'dc.related.datasetDOI','dc.related.datasetURL','dc.related.codeURL','dc.related.accessionNumber'
		
		$dataAvailabilityStatements =  getValues($irts, "SELECT source, idInSource, value FROM `metadata`
		WHERE `source` LIKE 'irts'
		AND `field` LIKE 'dc.description.dataAvailability'
		AND deleted IS NULL", array('source', 'idInSource', 'value'), 'arrayOfValues');
		
		foreach($dataAvailabilityStatements as $dataAvailabilityStatement)
		{
			print_r($dataAvailabilityStatement);
			
			$idInIRTS = $dataAvailabilityStatement['idInSource'];
			
			$dataAvailabilityStatement = $dataAvailabilityStatement['value'];
			
			//Check for GitHub URLs
			preg_match_all('/https:\/\/github.com\/[-a-zA-Z0-9@:%_\+~\#?&\/\/=]*/', $dataAvailabilityStatement, $githubURLsGroups);
			
			foreach($githubURLsGroups as $githubURLsGroup)
			{
				foreach($githubURLsGroup as $githubURL)
				{
					echo $githubURL.PHP_EOL;
					
					$recordTypeCounts['all']++;
					
					$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
					WHERE `source` LIKE 'irts' 
					AND `idInSource` LIKE '$idInIRTS' 
					AND (
							`field` IN ('dc.relation.issupplementedby','dc.relation.references') 
							AND value LIKE 'URL:$githubURL%' 
							AND `deleted` IS NULL
						)
					OR	
						(
							`field` LIKE 'dc.related.codeURL' 
							AND value LIKE '$githubURL%' 
						)", array('idInSource'));
					
					if(empty($existing))
					{
						$place = getValues($irts, "SELECT place FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '".$abstract['idInSource']."'  
							AND `field` LIKE 'dc.related.codeURL'
							ORDER BY place DESC
							LIMIT 1", array('place'), 'singleValue');
							
						if(empty($place))
						{
							$place = 1;
						}
						else
						{
							$place = $place+1;
						}
						
						$result = saveValue('irts', $idInIRTS, 'dc.related.codeURL', $place, $githubURL, NULL);
						
						$recordTypeCounts['new']++;
					}
					else
					{
						$recordTypeCounts['unchanged']++;
					}
				}
			}
			
			//Check for BioProject Numbers
			preg_match_all('/PRJ[A-Z]{2}[0-9]*/', $dataAvailabilityStatement, $accessionNumbersGroups);
			
			foreach($accessionNumbersGroups as $accessionNumbersGroup)
			{
				foreach($accessionNumbersGroup as $accessionNumber)
				{
					print_r($accessionNumber);
					
					$recordTypeCounts['all']++;
					
					$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
					WHERE `source` LIKE 'irts' 
					AND `idInSource` LIKE '$idInIRTS' 
					AND (
							`field` IN ('dc.relation.issupplementedby','dc.relation.references') 
							AND value LIKE 'accessionNumber:$accessionNumber' 
							AND `deleted` IS NULL
						)
					OR	
						(
							`field` LIKE 'dc.related.accessionNumber' 
							AND value LIKE '$accessionNumber' 
						)", array('idInSource'));
					
					if(empty($existing))
					{
						$place = getValues($irts, "SELECT place FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '$idInIRTS'  
							AND `field` LIKE 'dc.related.accessionNumber'
							ORDER BY place DESC
							LIMIT 1", array('place'), 'singleValue');
							
						if(empty($place))
						{
							$place = 1;
						}
						else
						{
							$place = $place+1;
						}
						
						$result = saveValue('irts', $idInIRTS, 'dc.related.accessionNumber', $place, $accessionNumber, NULL);
						
						$recordTypeCounts['new']++;
					}
					else
					{
						$recordTypeCounts['unchanged']++;
					}
				}
			}
			
			//Check for DOIs
			preg_match_all('/10\.\d+\/[^(\s\>\"\<;)]+/', $dataAvailabilityStatement, $doiGroups);
			
			foreach($doiGroups as $doiGroup)
			{
				foreach($doiGroup as $doi)
				{
					$lastChar = substr($doi, -1);
					
					if(in_array($lastChar, array('.',',')))
					{
						$doi = substr($doi, 0, -1);
					}
					
					echo $doi.PHP_EOL;
					
					$recordTypeCounts['all']++;
					
					$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
					WHERE `source` LIKE 'irts' 
					AND `idInSource` LIKE '$idInIRTS' 
					AND (
							`field` IN ('dc.relation.issupplementedby','dc.relation.references') 
							AND value LIKE 'DOI:$doi%' 
							AND `deleted` IS NULL
						)
					OR	
						(
							`field` LIKE 'dc.related.datasetDOI' 
							AND value LIKE '$doi%' 
						)
					OR	
						(
							`field` LIKE 'dc.identifier.doi' 
							AND value LIKE '$doi%' 
						)", array('idInSource'));
					
					if(empty($existing))
					{
						$place = getValues($irts, "SELECT place FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '$idInIRTS'  
							AND `field` LIKE 'dc.related.datasetDOI'
							ORDER BY place DESC
							LIMIT 1", array('place'), 'singleValue');
							
						if(empty($place))
						{
							$place = 1;
						}
						else
						{
							$place = $place+1;
						}
						
						$result = saveValue('irts', $idInIRTS, 'dc.related.datasetDOI', $place, $doi, NULL);
						
						$recordTypeCounts['new']++;
					}
					else
					{
						$recordTypeCounts['unchanged']++;
					}
				}
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
