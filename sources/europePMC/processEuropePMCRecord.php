<?php
	//Define function to process Europe PMC results
	function processEuropePMCRecord($record)
	{
		global $irts, $newInProcess, $errors;
		
		$source = 'europePMC';
		
		$idInSource = $record['id'];
		
		echo $idInSource.PHP_EOL;
		
		$sourceDataAsJSON = json_encode($record);
		
		//Save copy of item as JSON
		$result = saveSourceData($irts, $source, $idInSource, $sourceDataAsJSON, 'JSON');
		$recordType = $result['recordType'];
		
		foreach($record as $field => $value)
		{
			if($field === 'authorList')
			{
				$place = 1;
				foreach($value['author'] as $author)
				{
					$field = 'dc.contributor.author';
					
					if(isset($author['collectiveName']))
					{
						$value = $author['collectiveName'];						
					}
					elseif(isset($author['firstName']))
					{
						$value = $author['lastName'].', '.$author['firstName'];	
					}
					elseif(isset($author['initials']))
					{
						$value = $author['lastName'].', '.$author['initials'];	
					}
					else
					{
						$value = $author['fullName'];	
					}
					
					//echo $value;
					
					$parentRowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $value, NULL);
					
					if(isset($author['authorAffiliationDetailsList']))
					{
						foreach($author['authorAffiliationDetailsList']['authorAffiliation'] as $affplace => $affiliation)
						{
							if(!empty($affiliation['affiliation']))
							{
								$field = 'dc.contributor.affiliation';

								$rowID = mapTransformSave($source, $idInSource, '', $field, '', $affplace, $affiliation['affiliation'], $parentRowID);
							}
						}
					}
					
					if(isset($author['authorId']))
					{
						//print_r($author['authorId']);
						
						if((string)$author['authorId']['type']==='ORCID')
						{
							$field = 'dc.identifier.orcid';

							$rowID = mapTransformSave($source, $idInSource, '', $field, '', 1, $author['authorId']['value'], $parentRowID);
						}
					}

					$place++;
				}
			}			
			elseif(is_array($value))
			{
				$childPlace = 1;
				foreach($value as $childField => $childValue)
				{
					$childField = "$field.$childField";
					if(is_array($childValue))
					{
						$grandChildPlace = 1;
						foreach($childValue as $grandChildField => $grandChildValue)
						{
							if(is_int($grandChildField))
							{
								$grandChildField = "$childField";
								//$grandChildPlace = $grandChildField;
							}
							else
							{
								$grandChildField = "$childField.$grandChildField";
							}
							
							if(is_array($grandChildValue))
							{
								$grandChildValue = json_encode($grandChildValue);
							}
							
							$rowID = mapTransformSave($source, $idInSource, '', $grandChildField, '', $grandChildPlace, $grandChildValue, NULL);
							
							$grandChildPlace++;
						}
					}
					else
					{
						$rowID = mapTransformSave($source, $idInSource, '', $childField, '', $childPlace, $childValue, NULL);
						
						//$childPlace++;
					}					
				}
			}
			elseif($field !== 'citedByCount')
			{
				$rowID = mapTransformSave($source, $idInSource, '', $field, '', 1, $value, NULL);
			}
		}
		
		if(strpos($idInSource, 'PPR') !== FALSE)
		{
			$result = saveValue($source, $idInSource, 'dc.type', 1, 'Preprint', NULL);
		}
		else
		{
			$result = saveValue($source, $idInSource, 'dc.type', 1, 'Article', NULL);
		}
		
		return $recordType;
	}
