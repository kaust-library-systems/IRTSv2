<?php

/*

**** This file responsible is responsible for setting the inverse relation on an existing record when a new record is added that has a relationship to the existing record.

** Parameters :
	$itemID: DSpace item ID of the new item.
	
*/

//--------------------------------------------------------------------------------------------------------------------------------------------------
function setInverseRelations($itemID)
{
	//init 
	global $irts;

	$report = '';
	$errors = array();
	
	$source = 'dspace';
	$relations = array();
	
	//$prefixes = array('DOI' => 'dc.identifier.doi' , 'bioproject' => 'dc.identifier.bioproject', 'biosample' => 'dc.identifier.biosample', 'Handle' => 'dc.identifier.uri');
	$prefixes = array('DOI' => 'dc.identifier.doi','bioproject' => 'dc.identifier.bioproject','biosample' => 'dc.identifier.biosample','github' => 'dc.identifier.github','arXiv' => 'dc.identifier.arxivid', 'Handle' => 'dc.identifier.uri');
	
	$identifierToAdd = '';
	foreach($prefixes as $prefix => $field)
	{
		$identifierToAdd = getValues($irts, setSourceMetadataQuery($source, $itemID, NULL, $field), array('value'), 'singleValue');
		
		if(!empty($identifierToAdd))
		{
			$identifierToAdd = $prefix.':'.$identifierToAdd;
			
			break;
		}
	}
	
	if(!empty($identifierToAdd))
	{
		// create inverse relations array
		$inverseRelations = array(
			'dc.relation.issupplementto' => 'dc.relation.issupplementedby', 
			'dc.relation.haspart' => 'dc.relation.ispartof', 
			'dc.relation.isreferencedby' => 'dc.relation.references'
		);
		
		$inverseRelations = array_merge($inverseRelations, array_flip($inverseRelations));
		
		//get relation field values
		foreach($inverseRelations as $relationField => $inverseField)
		{
			$relations[$relationField] = getValues($irts, setSourceMetadataQuery($source, $itemID, NULL, $relationField), array('value'));
			
			if(!empty($relations[$relationField]))
			{
				$report .= '<br> - Relation Field:'.$relationField.PHP_EOL;
				foreach($relations[$relationField] as $relatedIdentifier)
				{
					$report .= '<br> -- Related Identifier:'.$relatedIdentifier.PHP_EOL;
					
					foreach($prefixes as $prefix => $field)
					{
						if(strpos($relatedIdentifier, $prefix.':') !== FALSE)
						{
							$relatedIdentifier = str_replace($prefix.':', '', $relatedIdentifier);
							
							$relatedItemHandle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, $field, $relatedIdentifier), array('idInSource'), 'singleValue');

							if(!empty($relatedItemHandle))
							{
								$report .= '<br> -- Related Item Handle:'.$relatedItemHandle.PHP_EOL;

								//get existing metadata and itemID
								$response = dspaceGetItemByHandle($relatedItemHandle);

								if($response['status'] == 'success')
								{
									$newMetadata = array();
									
									$item = json_decode($response['body'], TRUE);

									//metadata as simple array
									$record = dSpaceMetadataToArray($item['metadata']);

									//include all relation fields in the new metadata so that all relations appear in display.relations
									foreach($record as $field => $values)
									{
										if(strpos($field, 'dc.relation.') !== FALSE)
										{
											$newMetadata[$field] = $record[$field];
										}
									}

									//add any existing values in the inverse field to the new metadata
									if(isset($record[$inverseField]))
									{
										if(!in_array($identifierToAdd, $newMetadata[$inverseField]))
										{
											//add the new identifier to the inverse field
											$newMetadata[$inverseField][] = $identifierToAdd;
										}
									}
									else
									{
										//add the new identifier to the inverse field
										$newMetadata[$inverseField][] = $identifierToAdd;
									}

									//Set the display.relations field
									$result = setDisplayRelationsField($newMetadata);
					
									$newMetadata = $result['record'];

									$response = dspacePrepareAndApplyPatchToItem($relatedItemHandle, $newMetadata, __FUNCTION__);
									
									$report .= '<br> -- status of display.relations field: '.$response['status'];
									
									if(in_array($response['status'], array('skipped','failed')))
									{
										$report .= '<br> -- SET INVERSE RELATIONS FAILURE: <br> -- Response received was: '.print_r($response, TRUE).'<br> -- Posted JSON was: '.$json.PHP_EOL;
									}
									else
									{
										$report .= '<br> -- Inverse relation set successfully.'.PHP_EOL;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	return($report);
}