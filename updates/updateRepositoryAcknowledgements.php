<?php	
	//Define function to update the acknowledgements information in the repository
	function updateRepositoryAcknowledgements($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$count = 0;
		
		$token = loginToDSpaceRESTAPI();

		$unmatched = array();
		
		$toCheck = array();
		
		//Select all items with no known matches
		/* $result = $irts->query("SELECT DISTINCT m.`idInSource` handle, m.value ack, m2.value doi
			FROM `metadata` m 
			LEFT JOIN metadata m2 USING(idInSource) 
			LEFT JOIN metadata i ON i.value = m2.value
			WHERE m.`source` LIKE 'repository' 
			AND m.`field` LIKE 'dc.description.sponsorship' 
			AND m.deleted IS NULL
			AND m2.`source` LIKE 'repository' 
			AND m2.`field` LIKE 'dc.identifier.doi' 
			AND m2.deleted IS NULL
			AND i.`source` LIKE 'irts' 
			AND i.`field` LIKE 'dc.identifier.doi' 
			AND i.deleted IS NULL 
			AND i.idInSource NOT IN (
			SELECT `idInSource` FROM metadata WHERE field IN ('kaust.acknowledged.supportUnit', 'kaust.grant.number', 'kaust.acknowledgement.type', 'local.acknowledgement.type'))"); */
			
		$result = $irts->query("SELECT m.idInSource itemID, GROUP_CONCAT(m3.value SEPARATOR '||') AS `values` FROM `metadata` m 
			LEFT JOIN metadata m2 USING(value)
			LEFT JOIN metadata m3 ON m2.idInSource = m3.idInSource
			WHERE m.`source` LIKE 'dspace' 
			AND m.`field` LIKE 'dc.identifier.doi' 
			AND m.deleted IS NULL 
			AND m2.`source` LIKE 'irts' 
			AND m2.`field` LIKE 'dc.identifier.doi' 
			AND m2.deleted IS NULL 
			AND m3.`source` LIKE 'irts' 
			AND m3.`field` LIKE 'kaust.acknowledged.supportUnit' 
			AND m3.deleted IS NULL
			GROUP BY itemID
			");	
			
		while($row = $result->fetch_assoc())
		{
			$recordTypeCounts['all']++;
			
			$problem = '';
			
			$changed = '';
			
			$itemID = $row['itemID'];
			
			echo $itemID.PHP_EOL;
			
			$values = explode('||', $row['itemID']);
			
			$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);
				
			$metadata = dSpaceJSONtoMetadataArray($json);
			
			if(isset($metadata['kaust.acknowledged.supportUnit']))
			{
				//print_r($metadata['kaust.acknowledged.supportUnit']);
				
				//$recordTypeCounts['skipped']++;
				
				//echo ' - skipped'.PHP_EOL;
				
				foreach($metadata['kaust.acknowledged.supportUnit'] as $key => $unit)
				{
					$value = $unit['value'];
					
					$orgID = getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.org.name', $value), array('idInSource'), 'singleValue');
					
					if(empty($orgID))
					{
						$orgID = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `source` = 'local' AND idInSource LIKE 'org_%' AND `field` = 'local.name.variant' AND value = '$value' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
						
						if(!empty($orgID))
						{
							$orgName = getValues($irts, setSourceMetadataQuery('local', $orgID, NULL, 'local.org.name'), array('value'), 'singleValue');
							
							if(!empty($orgName))
							{
								echo $value.' --> '.$orgName.PHP_EOL;
								
								$metadata['kaust.acknowledged.supportUnit'][$key]['value'] = $orgName;
								
								$changed = 'yes';
							}
						}
					}
				}
			}
			/* else
			{
				foreach($values as $value)
				{
					$orgID = getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.org.name', $value), array('idInSource'), 'singleValue');
					
					if(!empty($orgID))
					{
						$metadata['kaust.acknowledged.supportUnit'][] = $value;
						
						$changed = 'yes';
					}
					else
					{
						$orgID = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `source` = 'local' AND idInSource LIKE 'org_%' AND `field` = 'local.name.variant' AND value = '$value' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
						
						if(!empty($orgID))
						{
							$orgName = getValues($irts, setSourceMetadataQuery('local', $orgID, NULL, 'local.org.name'), array('value'), 'singleValue');
							
							if(!empty($orgName))
							{
								echo $value.' --> '.$orgName.PHP_EOL;
								
								$metadata['kaust.acknowledged.supportUnit'][] = $orgName;
								
								$changed = 'yes';
							}
						}
					}
				}
			} */
			
			if($changed === 'yes')
			{
				$metadata = appendProvenanceToMetadata($itemID, $metadata, __FUNCTION__);

				$json = prepareItemMetadataAsDSpaceJSON($metadata);

				$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
				
				$recordTypeCounts['modified']++;
				
				echo ' - modified'.PHP_EOL;
			}
			else
			{
				$recordTypeCounts['unchanged']++;
				
				echo ' - unchanged'.PHP_EOL;
			}
			
			
			/* $handle = $row['handle'];
			
			$ack = $row['ack'];
			
			$doi = $row['doi'];			
			
			$idInIRTS = getValues($irts, "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'irts' AND `field` LIKE 'dc.identifier.doi' AND value LIKE '$doi' AND `deleted` IS NULL ORDER BY idInSource ASC", array('idInSource'), 'singleValue');
			
			if(!empty($idInIRTS))
			{
				if(strpos($ack, 'KAUST')!==FALSE)
				{
					$matches = array();
					foreach($knownAcknowledgedUnits as $unit)
					{
						if(!in_array($unit, array('HPC','IT','RV','greenhouse')))
						{
							if(strpos($ack, $unit)!==FALSE)
							{
								$matches[] = $unit;
							}
						}
					}
					
					foreach($matches as $checkMatch)
					{
						foreach($matches as $match)
						{
							if($match !== $checkMatch)
							{
								if(strpos($match,$checkMatch)!==FALSE)
								{
									if (($key = array_search($checkMatch, $matches)) !== false) 
									{
										unset($matches[$key]);
									}
								}
							}
						}
					}
					$unitMatches = array_unique($matches);
					
					$matches = array();
					foreach($knownAcknowledgedGrants as $grant)
					{
						if (!ctype_digit($grant))
						{
							if(strpos($ack, $grant)!==FALSE)
							{
								$matches[] = $grant;
							}
						}
					}

					foreach($matches as $checkMatch)
					{
						foreach($matches as $match)
						{
							if($match !== $checkMatch)
							{
								if(strpos($match,$checkMatch)!==FALSE)
								{
									if (($key = array_search($checkMatch, $matches)) !== false) 
									{
										unset($matches[$key]);
									}
								}
							}
						}
					}
					$grantMatches = array_unique($matches);					
					
					if(!empty($grantMatches)||!empty($unitMatches))
					{
						$count++;
						
						echo PHP_EOL.$count.") Handle: $handle - DOI: $doi - idInIRTS: $idInIRTS".PHP_EOL."-- Original Acknowledgements: $ack".PHP_EOL;
						
						echo 'Matched units: '.implode(', ', $unitMatches).PHP_EOL;
						
						echo 'Matched grants: '.implode(', ', $grantMatches).PHP_EOL;
						
						$field = 'irts.check.acknowledgement';
						
						$rowID = mapTransformSave('irts', $idInIRTS, '', $field, '', 1, 'yes', NULL);
					}
				}
			} */
			ob_flush();
		}
			
		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
		
		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
