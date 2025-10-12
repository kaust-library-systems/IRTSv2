<?php	

/*

**** This functions updates the database entries for persons

** Parameters :
	$persons : array of persons

*/
//-----------------------------------------------------------------------------------------------------------

function processLocalPersons($persons, $reprocess)
{
	global $irts, $errors;
	
	//remove time limit on execution as especially the initial run will exceed the default limit
	set_time_limit(0);
	
	//local function report	and counts
	$report = '';
	$recordTypeCounts['all'] = 0;
	$recordTypeCounts['skipped'] = 0;
	$recordTypeCounts['new'] = 0;
	$recordTypeCounts['modified'] = 0;
	$recordTypeCounts['unchanged'] = 0;
	$recordTypeCounts['relation'] = 0;	
	
	#set the common values
	$source = 'local';
	$place = 1;
	$parentRowID = NULL;
	
	$personMapping = array(
		'personnelNumber' => 'local.person.personnelNumber',
		'studentNumber' => 'local.person.studentNumber',
		'kaustid' => 'local.person.id',
		'email' => 'local.person.email',
		'username' => 'local.person.username',
		'preferredFirstName' => 'local.person.preferredFirstName',
		'lastName' => 'local.person.lastName',
		'firstName' => 'local.person.firstName',
		'middleName' => 'local.person.middleName'
	);
	
	$relationMapping = array(
		'orgID' => 'local.org.id',
		'jobTitle' => 'local.position.type',
		'jobDescription' => 'local.person.title',
		'startDate' => 'local.date.start',
		'endDate' => 'local.date.end',
		'employmentType' => 'local.employment.type',
		'staffType' => 'local.staff.type',
		'studentType' => 'local.student.type'
	);
	
	foreach($persons as $person)
	{
		$recordTypeCounts['all']++;
		
		//there are a small number of entries that have a personnel number, but no KAUST ID, these will be ignored
		if(empty($person['kaustid']))
		{
			$recordTypeCounts['skipped']++;
		}
		else
		{
			$process = TRUE;
			
			$idInSource = $person['kaustid'];
			
			$sourceDataAsJSON = json_encode($person);
			
			//Save copy of item JSON
			$result = saveSourceData($irts, $source, $idInSource, $sourceDataAsJSON, 'JSON');

			$recordType = $result['recordType'];
			
			$recordTypeCounts[$recordType]++;
			
			if($recordType === 'unchanged' && !in_array($reprocess, array('both','persons')))
			{
				$process = FALSE;
			}
			
			if($process)
			{
				$report .= $idInSource.PHP_EOL;
				$report .= ' - person source data status: '.$recordType.PHP_EOL;
				
				foreach($person as $field => $value)
				{
					if(in_array($field, array_keys($personMapping)))
					{
						$place = 1;
						
						$result = saveValue($source, $idInSource, $personMapping[$field], $place, $value, NULL);

						if($result['status'] !== 'unchanged')
						{
							$report .= ' -- '.$field.' '.$result['status'].' value:'.$value.PHP_EOL;
							
							if(!isset($recordTypeCounts[$result['status'].' '.$field]))
							{
								$recordTypeCounts[$result['status'].' '.$field] = 1;
							}
							else
							{
								$recordTypeCounts[$result['status'].' '.$field]++;
							}
						}
					}
					
					if($field === 'relations')
					{
						foreach($value as $relationID => $relation)
						{
							$recordTypeCounts['relation']++;
							
							//delete merged relations
							if(isset($relation['mergedStartDates']))
							{
								foreach($relation['mergedStartDates'] as $mergedStartDate)
								{
									$mergedRelationRowID = getValues($irts, "SELECT rowID FROM metadata 
										WHERE source = '$source' 
										AND idInSource = '$idInSource'
										AND field LIKE 'local.personOrgRelation.id'
										AND deleted IS NULL
										AND rowID IN (
											SELECT parentRowID FROM metadata 
											WHERE source = '$source' 
											AND idInSource = '$idInSource'
											AND field LIKE 'local.date.start'
											AND value LIKE '$mergedStartDate'
											AND deleted IS NULL
											)
										AND rowID IN (
											SELECT parentRowID FROM metadata 
											WHERE source = '$source' 
											AND idInSource = '$idInSource'
											AND field LIKE 'local.person.title'
											AND deleted IS NULL
											)", array('rowID'), 'singleValue');
									
									if(!empty($mergedRelationRowID))
									{
										update($irts, 'metadata', array("deleted"), array(date("Y-m-d H:i:s"), $mergedRelationRowID), 'rowID');
										
										//Recursively mark any children of this row as deleted as well
										markExtraMetadataAsDeleted($source, $idInSource, $mergedRelationRowID, '', '', '');
										
										$report .= ' -- personOrgRelation with merged start date: '.$mergedStartDate.' deleted'.PHP_EOL;
										
										if(!isset($recordTypeCounts['personOrgRelation with merged start date deleted']))
										{
											$recordTypeCounts['personOrgRelation with merged start date deleted'] = 1;
										}
										else
										{
											$recordTypeCounts['personOrgRelation with merged start date deleted']++;
										}
									}
								}
							}
							
							$relationStartDate = $relation['startDate'];
							$relationOrgID = $relation['orgID'];
							
							if(!empty($relation['jobDescription']))
							{
								//We assume that each person will only have one position with a given start date, the exception is faculty who will not have a job title for their extra affiliations
								$existingRelationRowID = getValues($irts, "SELECT rowID FROM metadata 
									WHERE source = '$source' 
									AND idInSource = '$idInSource'
									AND field LIKE 'local.personOrgRelation.id'
									AND deleted IS NULL
									AND rowID IN (
										SELECT parentRowID FROM metadata 
										WHERE source = '$source' 
										AND idInSource = '$idInSource'
										AND field LIKE 'local.date.start'
										AND value LIKE '$relationStartDate'
										AND deleted IS NULL
										)
									AND rowID IN (
										SELECT parentRowID FROM metadata 
										WHERE source = '$source' 
										AND idInSource = '$idInSource'
										AND field LIKE 'local.person.title'
										AND deleted IS NULL
										)", array('rowID'), 'singleValue');
							}
							else
							{
								//faculty typically have 1 primary program affiliation and 1 primary center affiliation, these may change, but for now we do not know the start and end dates of the change and will instead keep 1 program relation and 1 center affiliation, just updating the org id, so the match will be on org type, not org id.
								$orgType = getValues($irts, "SELECT value FROM metadata 
									WHERE source = '$source' 
									AND idInSource = 'org_$relationOrgID'
									AND field LIKE 'local.org.type'
									AND deleted IS NULL
									", array('value'), 'singleValue');
								
								$existingRelationRowID = getValues($irts, "SELECT rowID FROM metadata 
								WHERE source = '$source' 
								AND idInSource = '$idInSource'
								AND field LIKE 'local.personOrgRelation.id'
								AND deleted IS NULL
								AND rowID IN (
									SELECT parentRowID FROM metadata 
									WHERE source = '$source' 
									AND field LIKE 'local.date.start'
									AND value LIKE '$relationStartDate'
									AND deleted IS NULL
									)
								AND rowID NOT IN (
									SELECT parentRowID FROM metadata 
									WHERE source = '$source'
									AND idInSource = '$idInSource'							
									AND field LIKE 'local.person.title'
									AND deleted IS NULL							
									) 
								AND rowID IN (
									SELECT parentRowID FROM metadata 
									WHERE source = '$source' 
									AND idInSource = '$idInSource'
									AND field LIKE 'local.org.id'
									AND CONCAT('org_', value) IN(
										SELECT idInSource FROM metadata 
										WHERE source = '$source' 
										AND field LIKE 'local.org.type'
										AND value LIKE '$orgType'
										AND deleted IS NULL
									)
									AND deleted IS NULL
									)", array('rowID'), 'singleValue');
							}
									
							if(empty($existingRelationRowID))
							{
								$existingPlace = getValues($irts, "SELECT `place` FROM `metadata` 
										WHERE source = '$source' 
										AND idInSource = '$idInSource' 
										AND `field` = 'local.personOrgRelation.id' 
										AND deleted IS NULL 
										ORDER BY `place` 
										DESC LIMIT 1", array('place'), 'singleValue');

								if(!empty($existingPlace))
								{
									$place = $existingPlace+1;
								}
								else
								{
									$place = 1;
								}
								
								$result = saveValue($source, $idInSource, 'local.personOrgRelation.id', $place, $relationID, NULL);
								
								$parentRowID = $result['rowID'];
								
								$report .= ' -- local.personOrgRelation.id '.$result['status'].' value:'.$relationID.PHP_EOL;
							
								if(!isset($recordTypeCounts[$result['status'].' local.personOrgRelation.id']))
								{
									$recordTypeCounts[$result['status'].' local.personOrgRelation.id'] = 1;
								}
								else
								{
									$recordTypeCounts[$result['status'].' local.personOrgRelation.id']++;
								}
							}
							else
							{
								$parentRowID = $existingRelationRowID;
							}
							
							foreach($relation as $field => $value)
							{
								if(in_array($field, array_keys($relationMapping)))
								{
									//do not save empty values (some job titles are empty) and do not save end dates with value '9999-12-31'
									if(!empty($value) && $value !== '9999-12-31')
									{
										$result = saveValue($source, $idInSource, $relationMapping[$field], 1, $value, $parentRowID);

										if($result['status'] !== 'unchanged')
										{
											$report .= ' -- '.$field.' '.$result['status'].' value:'.$value.PHP_EOL;
											
											if(!isset($recordTypeCounts[$result['status'].' '.$field]))
											{
												$recordTypeCounts[$result['status'].' '.$field] = 1;
											}
											else
											{
												$recordTypeCounts[$result['status'].' '.$field]++;
											}
										}
									}
									
									//check for and mark as deleted any previously entered endDate if the position is now current (this may happen because of position merging changes)
									if($field === 'endDate' && $value === '9999-12-31')
									{
										$existingEndDateRowID = getValues($irts, "SELECT `rowID` FROM `metadata` 
										WHERE source = '$source' 
										AND idInSource = '$idInSource' 
										AND parentRowID = '$parentRowID'
										AND `field` = 'local.date.end' 
										AND deleted IS NULL", array('rowID'), 'singleValue');
										
										if(!empty($existingEndDateRowID))
										{
											update($irts, 'metadata', array("deleted"), array(date("Y-m-d H:i:s"), $existingEndDateRowID), 'rowID');
											
											$report .= ' -- deleted '.$field.' rowID:'.$existingEndDateRowID.PHP_EOL;
											
											if(!isset($recordTypeCounts['deleted '.$field]))
											{
												$recordTypeCounts['deleted '.$field] = 1;
											}
											else
											{
												$recordTypeCounts['deleted '.$field]++;
											}
										}
									}
								}
							}
							unset($parentRowID);
						}
					}
				}
				
				$field = 'local.person.name';
				$place = 1;
				$standardName = '';
				$alternateNames = [];
				$existingName = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND idInSource = '$idInSource' AND `field` = '$field' AND place = $place AND deleted IS NULL", array('value'), 'singleValue');
				
				if(isset($person['preferredFirstName']))
				{
					$standardName = trim($person['lastName']).', '.trim($person['preferredFirstName']);
					$alternateNames[] = trim($person['lastName']).', '.trim($person['preferredFirstName']);
				}
				else
				{
					$standardName = trim($person['lastName']).', '.trim($person['firstName']);
				}
				
				$alternateNames[] = trim($person['lastName']).', '.trim($person['firstName']);
				if(isset($person['middleName']))
				{
					$alternateNames[] = trim($person['lastName']).', '.trim($person['firstName']).' '.trim($person['middleName']);
				}
				
				//do not replace standard names already in the database as they may have been set manually and should not be overwritten based on HR data
				if(empty($existingName))
				{
					$result = saveValue($source, $idInSource, $field, $place, $standardName, NULL);

					$report .= ' -- '.$field.' '.$result['status'].' value:'.$standardName.PHP_EOL;
					
					if(!isset($recordTypeCounts[$result['status'].' '.$field]))
					{
						$recordTypeCounts[$result['status'].' '.$field] = 1;
					}
					else
					{
						$recordTypeCounts[$result['status'].' '.$field]++;
					}
				}
				
				$alternateNames = array_unique($alternateNames);
				foreach($alternateNames as $alternateName)
				{
					$field = 'local.name.variant';
					$variantsAdded = generateNameVariants($idInSource, $alternateName);
					
					foreach($variantsAdded as $variantAdded)
					{
						$report .= ' -- '.$field.' new value:'.$variantAdded.PHP_EOL;
						
						if(!isset($recordTypeCounts['new '.$field]))
						{
							$recordTypeCounts['new '.$field] = 1;
						}
						else
						{
							$recordTypeCounts['new '.$field]++;
						}
					}
				}
			}
		}
	}
	
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	
	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
}
