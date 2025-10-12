<?php
	//Define function to prepare a record for review from the available metadata for a given item, based on a template
	function getRecord($source, $idInSource, $template = '')
	{
		global $irts;

		$record = array();
		
		$getRecordTime = microtime(true);

		if(empty($template)) //retrieve raw record in new structure
		{
			$rows = getValues($irts, "SELECT `rowID`,`parentRowID`,`field`,`place`,`value` FROM `metadata`
				WHERE `source` LIKE '$source'
				AND `idInSource` LIKE '$idInSource'
				AND `deleted` IS NULL
				ORDER BY `field`,`place`", array('rowID','parentRowID','field','place','value'));
	
			//print_r($rows);

			foreach($rows as $parent)
			{
				$parentPlace = 0;
				$childPlace = 0;

				if(empty($parent['parentRowID']))
				{
					$record[$parent['field']][$parentPlace]['value'] = $parent['value'];

					foreach($rows as $child)
					{
						if($child['parentRowID']===$parent['rowID'])
						{
							$record[$parent['field']][$parentPlace]['children'][$child['field']][$childPlace]['value'] = $child['value'];

							$childPlace++;
						}
					}
				}
				$parentPlace++;
			}
		}
		elseif($template === 'flat') //flat record without children fields and with only one value per field
		{
			$rows = getValues($irts, "SELECT `field`,`value` FROM `metadata`
				WHERE `source` LIKE '$source'
				AND `idInSource` LIKE '$idInSource'
				AND `deleted` IS NULL
				ORDER BY `field`", array('field','value'));

			foreach($rows as $row)
			{
				$record[$row['field']] = $row['value'];
			}
		}
		else //build record based on template
		{
			//print_r($template);

			$children = array();

			$doi = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, "dc.identifier.doi"), array('value'), 'singleValue');

			if(empty($doi) && $source == 'scopus')
			{
				$doi = getValues($irts, "SELECT doi.value
									FROM `metadata` `eid` 
									LEFT JOIN metadata `doi` USING(idInSource)
									WHERE eid.`source` LIKE 'irts' 
									AND eid.`field` LIKE 'dc.identifier.eid' 
									AND eid.value LIKE '$idInSource'
									AND eid.deleted IS NULL
									AND doi.field LIKE 'dc.identifier.doi' 
									AND doi.deleted IS NULL
								", array('value'), 'singleValue');
			}

			$authorRowIDs = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.contributor.author'), array('rowID', 'value'));

			if($source === 'repository') //build record in new structure
			{
				$record['dc.identifier.uri'] = array();

				$values = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.identifier.uri'), array('value'));

				foreach($values as $value)
				{
					$record['dc.identifier.uri'][]['value'] = $value;
				}

				$authorRowIDs = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.contributor.author'), array('rowID', 'value'));

				if(isset($_GET['itemType']))
				{
					$itemType = $_GET['itemType'];
				}
				else
				{
					$itemType = '';
				}

				//identify alternate source ids
				$additionalSourceIDs = array();

				if(!empty($doi))
				{
					foreach(array('scopus','irts','crossref','europePMC','ieee') as $additionalSource)
					{
						$additionalSourceID = getValues($irts, setSourceMetadataQuery($additionalSource, NULL, NULL, "dc.identifier.doi", $doi), array('idInSource'), 'singleValue');

						if(!empty($additionalSourceID))
						{
							$additionalSourceIDs[$additionalSource] = $additionalSourceID;
						}
					}
				}
				else
				{
					$eid = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, "dc.identifier.eid"), array('value'), 'singleValue');

					if(!empty($eid))
					{
						$additionalSourceID = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, "dc.identifier.eid", $eid), array('idInSource'), 'singleValue');

						if(!empty($additionalSourceID))
						{
							$additionalSourceIDs['irts'] = $additionalSourceID;
						}

						$additionalSourceIDs['scopus'] = $eid;
					}
					else
					{
						$title = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, "dc.title"), array('value'), 'singleValue');
						
						if(!empty($title))
						{
							$eid = getValues($irts, setSourceMetadataQuery('scopus', NULL, NULL, "dc.title", $title), array('idInSource'), 'singleValue');

							if(!empty($eid))
							{
								$additionalSourceIDs['scopus'] = $eid;
							}
						}
					}
				}

				//get additional source author row ids
				$additionalSources = array();

				foreach($additionalSourceIDs as $additionalSource => $additionalSourceID)
				{
					$additionalSources[$additionalSource]['idInSource'] = $additionalSourceID;

					$additionalSources[$additionalSource]['authorRowIDs'] = getValues($irts, setSourceMetadataQuery($additionalSource, $additionalSourceID, NULL, 'dc.contributor.author'), array('rowID', 'value'));

					//We assume that if the author counts match, that the author lists match
					//This does not account for different author ordering between sources
					if(count($additionalSources[$additionalSource]['authorRowIDs'])!==count($authorRowIDs))
					{
						unset($additionalSources[$additionalSource]['authorRowIDs']);
					}
				}

				foreach(array_keys($template['fields']) as $field)
				{
					$record[$field] = array();

					$values = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $field), array('value'));

					foreach($values as $value)
					{
						$record[$field][]['value'] = $value;
					}

					if(empty($record[$field]))
					{
						if($field === 'dc.relation.url')
						{
							if(!empty($doi))
							{
								$values = checkAdditionalSources($doi, $field, array('doi' => $doi));

								foreach($values as $value)
								{
									$record[$field][]['value'] = $value;
								}
							}
						}
						elseif($field === 'dc.contributor.affiliation')
						{
							//Affiliation fields do not have a NULL parentRowID and so are not picked up by the first query
							$values = array_unique(getValues($irts, setSourceMetadataQuery($source, $idInSource, TRUE, $field), array('value')));

							foreach($values as $value)
							{
								$record[$field][]['value'] = $value;
							}

							if(empty($record[$field]))
							{
								foreach($additionalSources as $additionalSource => $additionalData)
								{
									$values = array_unique(getValues($irts, setSourceMetadataQuery($additionalSource, $additionalData['idInSource'], TRUE, $field), array('value')));

									foreach($values as $value)
									{
										$record[$field][]['value'] = $value;
									}

									if(!empty($record[$field]))
									{
										break;
									}
								}
							}

							//reindex array
							$record[$field] = array_values($record[$field]);
						}
						else
						{
							if(!empty($doi))
							{
								$values = checkAdditionalSources($doi, $field);

								foreach($values as $value)
								{
									$record[$field][]['value'] = $value;
								}
							}
						}
					}

					//Check for child fields
					if(!empty($template['fields'][$field]['field']))
					{
						foreach($template['fields'][$field]['field'] as $child)
						{
							$children[] = $child;
							foreach($record[$field] as $key => $value)
							{
								if(is_int($key))
								{
									//This only retrieves children from the original source...
									if(isset($authorRowIDs[$key]))
									{
										$values = getValues($irts, setSourceMetadataQuery($source, $idInSource, $authorRowIDs[$key]['rowID'], $child), array('value'));

										foreach($values as $value)
										{
											$record[$field][$key]['children'][$child][]['value'] = $value;
										}
									}

									if(empty($record[$field][$key]['children'][$child]))
									{
										//echo $key;

										//print_r($record[$field][$child][$key]);

										//check alternate sources
										foreach($additionalSources as $additionalSource => $additionalData)
										{
											if(isset($additionalData['authorRowIDs'][$key]))
											{
												$values = getValues($irts, setSourceMetadataQuery($additionalSource, $additionalData['idInSource'], $additionalData['authorRowIDs'][$key]['rowID'], $child), array('rowID','value'));

												$childKey = 0;
												foreach($values as $row)
												{
													$record[$field][$key]['children'][$child][$childKey]['value'] = $row['value'];

													if($child === 'dc.contributor.affiliation')
													{
														$scopusAffIDs = getValues($irts, setSourceMetadataQuery($additionalSource, $additionalData['idInSource'], $row['rowID'], 'dc.identifier.scopusid'), array('value'));

														foreach($scopusAffIDs as $scopusAffID)
														{
															$record[$field][$key]['children'][$child][$childKey]['children']['dc.identifier.scopusid'][]['value'] = $scopusAffID;
														}
													}
													$childKey++;
												}
											}

											if(!empty($record[$field][$key]['children'][$child]))
											{
												break;
											}
										}

										if(empty($record[$field][$key]['children'][$child]))
										{
											//Set empty value so that it will display in the form for direct data entry
											$record[$field][$key]['children'][$child][] = '';
										}
									}
								}
							}
						}
					}
				}

				if(empty($record['dc.contributor.author']))
				{
					$record['dc.contributor.author'][] = '';
				}

				if(!isset($record['dc.contributor.author'][0]['children']['dc.contributor.affiliation']))
				{
					$record['dc.contributor.author']['dc.contributor.affiliation'][0][] = '';
				}

				//Most child metadata only belongs within the hierarchy not as a top-level field
				foreach($children as $child)
				{
					if($child!=='dc.contributor.affiliation')
					{
						unset($record[$child]);
					}
				}
			}
			else //build record in old IRTS structure
			{
				//identify alternate source ids
				$additionalSources = array();

				if(!empty($doi))
				{
					foreach(array('crossref','scopus','europePMC','ieee','irts') as $additionalSource)
					{
						if($source !== $additionalSource)
						{
							$additionalSourceID = getValues($irts, setSourceMetadataQuery($additionalSource, NULL, NULL, "dc.identifier.doi", $doi), array('idInSource'), 'singleValue');

							if(!empty($additionalSourceID))
							{
								$additionalSources[$additionalSource]['idInSource'] = $additionalSourceID;

								$additionalSources[$additionalSource]['authorRowIDs'] = getValues($irts, setSourceMetadataQuery($additionalSource, $additionalSourceID, NULL, 'dc.contributor.author'), array('rowID', 'value'));

								//We assume that if the author counts match, that the author lists match
								//This does not account for different author ordering between sources
								if(count($additionalSources[$additionalSource]['authorRowIDs'])!==count($authorRowIDs))
								{
									unset($additionalSources[$additionalSource]['authorRowIDs']);
								}
							}
						}
					}
				}

				foreach(array_keys($template['fields']) as $field)
				{
					if($field == 'dc.identifier.doi')
					{
						// Angewandte Chemie records should always have two DOIs with the International Edition DOI listed first
						if(strpos($doi, '10.1002/anie') !== FALSE)
						{
							$record[$field] = [$doi, str_replace('10.1002/anie', '10.1002/ange', $doi)];
						}
						elseif(strpos($doi, '10.1002/ange') !== FALSE)
						{
							$record[$field] = [str_replace('10.1002/ange', '10.1002/anie', $doi), $doi];
						}
						else
						{
							$record[$field] = [$doi];
						}
					}
					else
					{
						$record[$field] = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $field), array('value'));
					}					

					if($field == 'dc.title' || $field == 'dc.description.abstract')
					{
						if(isset($record[$field][0]))
						{
							$record[$field] = [standardizeTheUseOfTags($record[$field][0])];
						}
					}

					if($field === 'dc.date.issued')
					{
						if(isset($record[$field][0]))
						{
							if($record[$field][0] > TODAY)
							{
								$record[$field] = '';
							}
						}

						if($source === 'scopus')
						{
							$crossrefDate = checkAdditionalSources($doi, $field);
							if(!empty($crossrefDate))
							{
								$record[$field] = $crossrefDate;
							}
						}
						
						if($source === 'datacite')
						{
							if(!isset($record[$field][0]))
							{
								$dateCreated = getValues($irts, setSourceMetadataQuery($source, $doi, NULL, 'datacite.created'), array('value'), 'singleValue');
								
								$publicationYear = getValues($irts, setSourceMetadataQuery($source, $doi, NULL, 'datacite.publicationYear'), array('value'), 'singleValue');
								
								//If date created has the same year as the publication year, use the date created because it includes month and day.
								if(strpos($dateCreated, $publicationYear) !== FALSE)
								{
									$record[$field][] = explode('T', $dateCreated)[0];
								}
								else
								{
									$record[$field][] = $publicationYear;
								}
							}
						}
					}

					if($field === 'dc.publisher' && $source === 'scopus')
					{
						$crossrefPublisher = checkAdditionalSources($doi, $field);
						if(!empty($crossrefPublisher))
						{
							$record[$field] = $crossrefPublisher;
						}
					}

					if($field === 'dc.type' && $source === 'scopus')
					{
						$articleTypes = array('Review','Editorial','Letter','Short Survey','Note');

						if(isset($record[$field][0])){

							if(in_array($record[$field][0], $articleTypes))
							{
								$record[$field][0] = 'Article';
							}

							if($record[$field][0] === 'Chapter')
							{
								$record[$field][0] = 'Book Chapter';
							}
						}
					}

					//if the field is dc.type and the value does not match the item type, set it to the item type
					if($field === 'dc.type' && isset($_GET['itemType']) && !empty($_GET['itemType']))
					{
						if(isset($record[$field][0]) && $record[$field][0] !== $_GET['itemType'])
						{
							$record[$field][0] = $_GET['itemType'];
						}
					}

					//If the field is empty, check for values from additional sources
					if(empty($record[$field]))
					{
						if($field === 'dc.relation.url')
						{
							if($source === 'ieee')
							{
								$alternativeFields = array('ieee.html_url', 'ieee.abstract_url', 'ieee.pdf_url');

								foreach($alternativeFields as $alternate)
								{
									$value = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $alternate), array('value'), 'singleValue');

									if(!empty($value))
									{
										$record[$field][] = $value;
									}
								}
							}
							elseif ($source === 'arxiv')
							{
								$record[$field][0] = 'https://arxiv.org/pdf/'.$idInSource.'.pdf';
							}
							else
							{
								if(!empty($doi))
								{
									$record[$field] = checkAdditionalSources($doi, $field, array('doi' => $doi));
								}
							}
						}
						elseif($field === 'dc.contributor.affiliation')
						{
							//Affiliation fields do not have a NULL parentRowID and so are not picked up by the first query
							$record[$field] = array_unique(getValues($irts, setSourceMetadataQuery($source, $idInSource, TRUE, $field), array('value')));

							if(empty($record[$field]))
							{
								foreach($additionalSources as $additionalSource => $additionalData)
								{
									$record[$field] = array_unique(getValues($irts, setSourceMetadataQuery($additionalSource, $additionalData['idInSource'], TRUE, $field), array('value')));

									if(!empty($record[$field]))
									{
										break;
									}
								}
							}

							//reindex array
							$record[$field] = array_values($record[$field]);
						}
						else
						{
							if(!empty($doi))
							{
								$record[$field] = checkAdditionalSources($doi, $field);
							}
						}
					}

					if(!empty($template['fields'][$field]['field']))
					{
						foreach($template['fields'][$field]['field'] as $child)
						{
							$children[] = $child;
							foreach($record[$field] as $key => $value)
							{
								if(is_int($key))
								{
									//This only retrieves children from the original source...
									if(isset($authorRowIDs[$key]) && $child !== 'dc.version')
									{
										$record[$field][$child][$key] = getValues($irts, setSourceMetadataQuery($source, $idInSource, $authorRowIDs[$key]['rowID'], $child), array('value'));
									}

									if(empty($record[$field][$child][$key]))
									{
										//echo $key;
										//print_r($record[$field][$child][$key]);
										//check alternate sources
										foreach($additionalSources as $additionalSource => $additionalData)
										{
											if(isset($additionalData['authorRowIDs'][$key]))
											{
												$record[$field][$child][$key] = getValues($irts, setSourceMetadataQuery($additionalSource, $additionalData['idInSource'], $additionalData['authorRowIDs'][$key]['rowID'], $child), array('value'));
											}

											if(!empty($record[$field][$child][$key]))
											{
												break;
											}
										}

										if(empty($record[$field][$child][$key]))
										{
											$record[$field][$child][$key][] = '';
										}

										// if the field is that mean the parentRowID is not NULL
										if($child === 'dc.version')
										{
											$record[$field][$child][$key] = getValues($irts, "SELECT `value` FROM `metadata` WHERE `source` = 'arxiv' AND `idInSource` = '".$idInSource."' AND `field` = 'dc.version' AND `deleted` IS NULL ", array('value'));
										}
									}
								}
							}
						}
					}
				}

				if(empty($record['dc.contributor.author']) && ( !in_array($_GET['itemType'] , HANDLING_RELATIONS)))
				{
					$record['dc.contributor.author'][] = '';
				}

				//for blank records where authors are added manually, blanks also have to be set for the child fields, otherwise they will not appear in the form
				if(!isset($record['dc.contributor.author']['dc.contributor.affiliation']) && ( !in_array($_GET['itemType'] , HANDLING_RELATIONS)))
				{
					$record['dc.contributor.author']['irts.author.correspondingEmail'][0][] = '';
					$record['dc.contributor.author']['dc.contributor.affiliation'][0][] = '';
				}

				//Most child metadata only belongs within the hierarchy not as a top-level field
				foreach($children as $child)
				{
					if($child !== 'dc.contributor.affiliation')
					{
						unset($record[$child]);
					}
				}

				if(in_array($_GET['itemType'], HANDLING_RELATIONS))
				{
					//get relation fields
					$relationFields = getValues($irts, "SELECT field FROM `metadata` WHERE `source` LIKE '$source' AND `idInSource` LIKE '$idInSource' AND `field` LIKE 'dc.relation.%' AND `field` NOT LIKE 'dc.relation.url' AND `deleted` IS NULL", array('field'), 'arrayOfValues');

					foreach($relationFields as $relationField)
					{
						$record[$relationField] = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $relationField), array('value'));
						
						foreach($record[$relationField] as $relatedIdentifier)
						{
							$prefixes = array('DOI' => 'dc.identifier.doi', 'bioproject' => 'dc.identifier.bioproject', 'biosample' => 'dc.identifier.bioproject', 'github' => 'dc.identifier.github', 'Handle' => 'dc.identifier.uri',	'arXiv' => 'dc.identifier.arxivid');
							
							foreach($prefixes as $prefix => $idField)
							{
								if(strpos($relatedIdentifier, $prefix.':') !== FALSE)
								{
									// get the related publication handle
									$relatedPublicationHandle = getValues($irts, "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `field` = '$idField' AND value = '".str_replace($prefix.':', '', $relatedIdentifier)."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');

									if(!empty($relatedPublicationHandle) && !in_array($relatedPublicationHandle, $record['dc.related.publicationHandle']))
									{
										$record['dc.related.publicationHandle'][] = $relatedPublicationHandle;
									}

									// if the prefix is "DOI", also add the related publication DOI
									if($prefix === 'DOI')
									{
										if(!in_array($relatedIdentifier, $record['dc.related.publicationDOI']))
										{
											$record['dc.related.publicationDOI'][] = str_replace('DOI:', '', $relatedIdentifier);
										}							
									}								
								}
							}
						}
					}

					//if the record has a DOI, check the IRTS metadata for relationships to this DOI
					if(!empty($doi))
					{
						$relatedPublicationIdInIRTS = getValues($irts, "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'irts' AND `field` = 'dc.related.datasetDOI' AND value = '$doi' AND `deleted` IS NULL", array('idInSource'), 'singleValue');

						$relatedPublicationDOI = getValues($irts, "SELECT value FROM `metadata` WHERE `source` LIKE 'irts' AND `field` = 'dc.identifier.doi' AND idInSource = '$relatedPublicationIdInIRTS' AND `deleted` IS NULL", array('value'), 'singleValue');

						//if the related publication DOI is not already in the record, add it
						if(!empty($relatedPublicationDOI) && !in_array($relatedPublicationDOI, $record['dc.related.publicationDOI']))
						{
							$record['dc.related.publicationDOI'][] = $relatedPublicationDOI;
						}

						//if there is a related publication DOI, add it to the issupplementto field
						if(!empty($relatedPublicationDOI))
						{
							//if the issupplementto field is not set, create it
							if(!isset($record['dc.relation.issupplementto']))
							{
								$record['dc.relation.issupplementto'] = array();
							}

							//if the related publication DOI is not already in the issupplementto array, add it
							if(!in_array('DOI:'.$relatedPublicationDOI, $record['dc.relation.issupplementto']))
							{
								$record['dc.relation.issupplementto'][] = 'DOI:'.$relatedPublicationDOI;
							}
						}
					}

					//for Crossref dataset records
					if($source == 'crossref')
					{
						//move the authors to the creators field
						$record['dc.creator'] = $record['dc.contributor.author'];
						unset($record['dc.contributor.author']);

						//if the Crossref type is "dataset", set the type to "Dataset"
						if($record['dc.type'][0] === 'dataset')
						{
							$record['dc.type'][0] = 'Dataset';
						}
					}

					//if the record has a related publication DOI, but not a handle, check for a handle
					if(!empty($record['dc.related.publicationDOI']) && empty($record['dc.related.publicationHandle']))
					{
						foreach($record['dc.related.publicationDOI'] as $relatedPublicationDOI)
						{
							//get the related publication handle from the repository
							$relatedPublicationHandle = getValues($irts, "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `field` = 'dc.identifier.doi' AND value = '".$relatedPublicationDOI."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');

							//if the related publication handle is not already in the record, add it
							if(!empty($relatedPublicationHandle) && !in_array($relatedPublicationHandle, $record['dc.related.publicationHandle']))
							{
								$record['dc.related.publicationHandle'][] = $relatedPublicationHandle;
							}
						}
					}

					//if the record has a related publication DOI that is not in the issupplementto array, add it
					if(!empty($record['dc.related.publicationDOI']))
					{
						// if the issupplementto field is not set, create it
						if(!isset($record['dc.relation.issupplementto']))
						{
							$record['dc.relation.issupplementto'] = array();
						}

						foreach($record['dc.related.publicationDOI'] as $relatedPublicationDOI)
						{
							// if the related publication DOI is not already in the issupplementto array, add it
							if(!in_array('DOI:'.$relatedPublicationDOI, $record['dc.relation.issupplementto']))
							{
								$record['dc.relation.issupplementto'][] = 'DOI:'.$relatedPublicationDOI;
							}
						}
					}

					//if the record has a related publication handle, but not a DOI, check for a DOI
					if(!empty($record['dc.related.publicationHandle']) && empty($record['dc.related.publicationDOI']))
					{
						foreach($record['dc.related.publicationHandle'] as $relatedPublicationHandle)
						{
							//get the related publication DOI from the repository
							$relatedPublicationDOI = getValues($irts, "SELECT value FROM `metadata` WHERE `source` LIKE 'repository' AND `field` = 'dc.identifier.doi' AND idInSource = '".$relatedPublicationHandle."' AND `deleted` IS NULL", array('value'), 'singleValue');

							//if the related publication DOI is not already in the record, add it
							if(!empty($relatedPublicationDOI) && !in_array($relatedPublicationDOI, $record['dc.related.publicationDOI']))
							{
								$record['dc.related.publicationDOI'][] = $relatedPublicationDOI;
							}

							//if no related publication DOI was found, and the handle is not in the issupplementto array, add it
							if(empty($relatedPublicationDOI))
							{
								// if the issupplementto field is not set, create it
								if(!isset($record['dc.relation.issupplementto']))
								{
									$record['dc.relation.issupplementto'] = array();
								}

								// if the related publication handle is not already in the issupplementto array, add it
								if(!in_array('Handle:'.$relatedPublicationHandle, $record['dc.relation.issupplementto']))
								{
									// add the related publication handle to the issupplementto array
									$record['dc.relation.issupplementto'][] = 'Handle:'.$relatedPublicationHandle;
								}
							}
						}
					}

					// use the handle to get the authors and affiliations
					if(!empty($record['dc.related.publicationHandle']))
					{
						//prepare empty author array
						$record['dc.contributor.author'] = array();
						
						$authors = getValues($irts, "SELECT `rowID`, `value` FROM `metadata` WHERE `source` LIKE 'repository' AND `idInSource` = '".$record['dc.related.publicationHandle'][0]."'AND `field` = 'dc.contributor.author' AND `deleted` IS NULL", array('rowID', 'value'), 'arrayOfValues');
						
						$idInIRTS = getValues(
							$irts, 
							"SELECT idInSource from metadata 
								WHERE `source` = 'irts'
								AND field = 'dc.identifier.doi'
								AND value = (
									SELECT value from metadata 
									WHERE `source` = 'repository'
									AND idInSource = '".$record['dc.related.publicationHandle'][0]."'
									AND field = 'dc.identifier.doi'
									AND `deleted` IS NULL 
									LIMIT 1
								)
								AND `deleted` IS NULL 
								LIMIT 1", 
							array('idInSource'), 
							'singleValue');

						if(!empty($idInIRTS))
						{
							$record['dc.contributor.affiliation'] = array_values(array_unique(getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, TRUE, 'dc.contributor.affiliation'), array('value'))));
							
							$irtsAuthorRowIDs = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.contributor.author'), array('rowID', 'value'));

							//We assume that if the author counts match, that the author lists match
							//This does not account for different author ordering between sources
							if(count($irtsAuthorRowIDs)!==count($authors))
							{
								unset($irtsAuthorRowIDs);
							}
						}

						foreach ($authors as $key => $author)
						{
							//replace multiple spaces in author names
							$authorName = preg_replace('/\s+/', ' ', $author['value']);
							
							$record['dc.contributor.author'][] = $authorName;
							
							$record['dc.contributor.author']['dc.identifier.orcid'][$key][] = getValues($irts, setSourceMetadataQuery('repository', $record['dc.related.publicationHandle'][0], $author['rowID'], 'dc.identifier.orcid'), array('value'), 'singleValue');
							
							if(isset($irtsAuthorRowIDs[$key]))
							{
								$record['dc.contributor.author']['dc.contributor.affiliation'][$key] = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, $irtsAuthorRowIDs[$key]['rowID'], 'dc.contributor.affiliation'), array('value'));
							}
							else
							{
								$record['dc.contributor.author']['dc.contributor.affiliation'][$key][] = '';
							}
						}
					}
				}
			}
		}
		
		$getRecordTime = microtime(true)-$getRecordTime;

		insert($irts, 'messages', array('process', 'type', 'message'), array('getRecordTime', 'report', $source.' '.$idInSource.': '.$getRecordTime.' seconds'));

		$_SESSION['variables']['record']=$record;
		
		return $record;
	}
