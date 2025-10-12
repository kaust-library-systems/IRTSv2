<?php
	//Define function to identify KAUST departments mentioned in affiliation strings
	function controlLocalAffiliations($departments, $deptIDs, $unmatched, $flags)
	{
		global $irts, $message;

		$itemReport = '';

		//retrieve lists of values for easier checking
		$addressVariants = getValues($irts, "SELECT value FROM metadata WHERE source = 'local' AND field = 'local.address.variant' AND deleted IS NULL ORDER BY `value` ASC", array('value'), 'arrayOfValues');

		$orgNameVariants = getValues($irts, "SELECT value FROM metadata WHERE source = 'local' AND (field = 'local.org.name' OR (idInSource LIKE 'org_%' AND field = 'local.name.variant') OR (parentRowID IS NOT NULL AND field = 'local.name.variant')) AND deleted IS NULL ORDER BY `value` ASC", array('value'), 'arrayOfValues');

		$collectionNames = getValues($irts, "SELECT value FROM metadata WHERE source = 'dspace' AND field = 'dspace.collection.name' AND deleted IS NULL ORDER BY `value` ASC", array('value'), 'arrayOfValues');
		
		$facultyLabNames = getValues($irts, "SELECT value FROM metadata WHERE source = 'local' AND idInSource NOT LIKE 'org_%' AND field = 'local.org.name' AND deleted IS NULL ORDER BY `value` ASC", array('value'), 'arrayOfValues');

		$departments = array_unique($departments);
		$departments = array_filter($departments);

		foreach($departments as $place => $value)
		{
			if(in_array($value, $facultyLabNames))
			{
				unset($departments[$place]);
				
				$itemReport .= 'Faculty group name "'.$value.'" removed as department'.PHP_EOL;

				$flags[] = 'deptsChanged';
			}
			else
			{
				$matchedDeptIDs = array_unique(getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.org.name', $value), array('idInSource')));

				if(!empty($matchedDeptIDs))
				{
					if(count($matchedDeptIDs)===1)
					{
						$deptIDs[] = str_replace('org_', '', $matchedDeptIDs[0]);
					}
					else
					{
						$itemReport .= 'Department name "'.$value.'" matched more than one local org name: '.print_r($matchedDeptIDs, TRUE).PHP_EOL;

						$flags[] = 'mismatch';
					}
				}
				else
				{
					$matchedDeptIDs = array_unique(getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.name.variant', $value), array('idInSource')));

					if(!empty($matchedDeptIDs))
					{
						if(count($matchedDeptIDs)===1)
						{
							$deptIDs[] = str_replace('org_', '', $matchedDeptIDs[0]);
							if(!in_array($value, $collectionNames))
							{
								unset($departments[$place]);

								$itemReport .= 'Dept name variant "'.$value.'" removed'.PHP_EOL;

								$flags[] = 'deptsChanged';
							}
						}
						else
						{
							$itemReport .= 'Department name "'.$value.'" matched name variants for more than one local org: '.print_r($matchedDeptIDs, TRUE).PHP_EOL;

							$flags[] = 'mismatch';
						}
					}
					else
					{
						if(in_array($value, $addressVariants))
						{
							//remove the address variant if department IDs have been identified for the authors, or if there are other department strings
							if(!empty($deptIDs)||count($departments)>1)
							{
								unset($departments[$place]);

								$itemReport .= 'Address variant "'.$value.'" removed'.PHP_EOL;

								$flags[] = 'deptsChanged';
							}
						}
						else
						{
							$matchedAddresses = array();
							foreach($addressVariants as $address)
							{
								if(strpos($value, $address)!==FALSE)
								{
									$matchedAddresses[$address] = strlen($address);
								}
							}
							$cleaned = $value;
							
							//sort matches by length, so that long matches are removed first
							arsort($matchedAddresses);
							//print_r($matchedAddresses);
							foreach($matchedAddresses as $address => $length)
							{
								$cleaned = str_replace($address, '', $cleaned);
							}

							$cleaned = str_replace(array('Thuwal','Jeddah','Saudi','Arabia','KSA','23955','6900','Makkah Province','Makkah','4700','SA','Kingdom'), '', $cleaned);

							$cleanedCompletely = str_replace(array(',','.',';','-',' ','(',')','and','of','now','at'), '', $cleaned);

							if(empty($cleanedCompletely))
							{
								if(!empty($deptIDs)&&count($departments)>1)
								{
									unset($departments[$place]);

									$itemReport .= 'Address variant "'.$value.'" removed'.PHP_EOL;

									$flags[] = 'deptsChanged';
								}
							}
							else
							{
								$matchedNames = array();
								foreach($orgNameVariants as $orgNameVariant)
								{
									if(strpos($cleaned, $orgNameVariant)!==FALSE)
									{
										$matchedNames[$orgNameVariant] = strlen($orgNameVariant);
									}
								}
								
								//sort matches by length, so that long matches are removed first
								arsort($matchedNames);
								//print_r($matchedNames);

								foreach($matchedNames as $orgNameVariant => $length)
								{
									if(strpos($cleaned, $orgNameVariant)===FALSE)
									{
										unset($matchedNames[$orgNameVariant]);
									}
									else
									{
										$cleaned = str_replace($orgNameVariant, '', $cleaned);

										//only interested in dept ids for departments that have collections
										$deptID = getValues($irts,
										"SELECT idInSource FROM metadata WHERE source = 'local' AND field IN('local.org.name', 'local.name.variant') AND value = '$orgNameVariant' AND idInSource IN(SELECT idInSource FROM metadata WHERE source = 'local' AND field = 'dspace.collection.handle' AND deleted IS NULL) AND deleted IS NULL", array('idInSource'), 'singleValue');

										if(!empty($deptID))
										{
											$deptIDs[] = str_replace('org_', '', $deptID);
										}
										else
										{
											$itemReport .= 'No Dept ID found for matched org name variant "'.$orgNameVariant.'"'.PHP_EOL;

											$flags[] = 'mismatch';
										}
									}
								}

								$cleanedCompletely = str_replace(array(',','.',';','-',' ','(',')','and','of','now','at'), '', $cleaned);
								
								//remove the address variant if department IDs have been identified for the authors, or if there are other department strings
								if(!empty($deptIDs)||count($departments)>1)
								{
									unset($departments[$place]);

									$itemReport .= 'Address with department name variant "'.$value.'" removed'.PHP_EOL;

									$flags[] = 'deptsChanged';
								}
								
								/*if(empty($cleanedCompletely))
								{
									//remove the address variant if department IDs have been identified for the authors, or if there are other department strings
									if(!empty($deptIDs)||count($departments)>1)
									{
										unset($departments[$place]);
	
										$itemReport .= 'Address with department name variant "'.$value.'" removed'.PHP_EOL;
	
										$flags[] = 'deptsChanged';
									}
								}
								else
								{
									$unmatched['affiliations'][$value] = $cleanedCompletely;
									
									$itemReport .= 'Address ( "'.$value.'" ) failed to remove'.PHP_EOL;

									$flags[] = 'mismatch';
								}*/
							}
						}
					}				
				}
			}
		}
		
		return array('departments' => $departments, 'deptIDs' => $deptIDs, 'unmatched' => $unmatched, 'flags' => $flags, 'itemReport' => $itemReport);
	}					
