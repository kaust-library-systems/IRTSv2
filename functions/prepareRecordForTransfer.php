<?php
	/*

	**** Define function to prepare a record for transfer to DSpace based on IRTS metadata

	** Parameters :
		$template: metadata template
		$idInIRTS: identifier of the record in IRTS

	** Return :
		$record: record prepared for transfer to DSpace

	*/

	//--------------------------------------------------------------------------------------------
	function prepareRecordForTransfer($template, $idInIRTS)
	{
		global $irts;
		
		$source = 'irts';
		$record = array();
		
		$fields = array_unique(array_keys($template['fields']));

		//fields used in IRTS but not in DSpace
		$fieldsToIgnore = array(
			'dc.contributor.affiliation',
			'kaust.acknowledgement.type',
			'kaust.acknowledged.person',
			'dc.description.dataAvailability',
			'dc.related.accessionNumber',
			'dc.related.codeURL',
			'dc.related.datasetDOI',
			'dc.related.datasetURL',
			'dc.related.publicationDOI',
			'dc.related.publicationHandle',
			'dc.rights.embargolength',
			'dc.relationType',
			'dc.relatedIdentifier',
			'dc.identifier.handle',
			'dc.creator',
			'unpaywall.relation.url'
		);

		//add idInIRTS to the metadata
		$record['kaust.identifier.irts'] = $idInIRTS;

		//get metadata fields
		foreach($fields as $field)
		{
			if(!in_array($field, $fieldsToIgnore))
			{
				if( $field == 'dc.version')
				{
					//dc.version has non-null parent row id, so setSourceMetadataQuery does not work
					$record[$field] = getValues($irts, "SELECT `value` FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInIRTS' AND `field` = 'dc.version' AND `deleted` IS NULL ", array('value'));
				}
				else
				{
					$record[$field] = getValues($irts, setSourceMetadataQuery($source, $idInIRTS, NULL, $field), array('value'));
				}					
			}
		}

		//get relation fields
		$relationFields = getValues($irts, "SELECT field FROM `metadata` WHERE `source` LIKE '$source' AND `idInSource` LIKE '$idInIRTS' AND `field` LIKE 'dc.relation.%' AND `field` NOT LIKE 'dc.relation.url' AND `deleted` IS NULL", array('field'), 'arrayOfValues');
		
		//if a relation field is not in the DSpace metadata registry, it will cause an error on transfer.
		$relationsToSendToDspace = array('dc.relation.issupplementto', 'dc.relation.issupplementedby', 'dc.relation.haspart', 'dc.relation.ispartof', 'dc.relation.isreferencedby', 'dc.relation.references');

		foreach($relationFields as $relationField)
		{
			if(in_array($relationField, $relationsToSendToDspace))
			{
				$record[$relationField] = getValues($irts, setSourceMetadataQuery($source, $idInIRTS, NULL, $relationField), array('value'));
			}
		}
		
		//Check for relation fields and set the display.relations field as needed
		$result = setDisplayRelationsField($record);
		
		$record = $result['record'];

		// A review step record (such as Unpaywall) may not have the author field
		if(isset($record['dc.contributor.author']))
		{
			$facultyLabs = [];
			$deptIDs = [];
			$record['dc.contributor.institution'] = [];
			$record['dc.contributor.department'] = [];
			
			foreach($record['dc.contributor.author'] as $authorPlace=>$author)
			{
				//default flag
				$localPerson = FALSE;

				$affiliations = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$author), 'dc.contributor.affiliation'), array('value'), 'arrayOfValues');

				$orcid = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$author), 'dc.identifier.orcid'), array('value'), 'singleValue');

				//print_r($affiliations);

				foreach($affiliations as $affiliation)
				{
					//echo $affiliation.'<br>';
					if(institutionNameInString($affiliation))
					{
						// flag as local person
						$localPerson = TRUE;
						
						$record['dc.contributor.department'][] = $affiliation;
					}
					else
					{
						$record['dc.contributor.institution'][] = $affiliation;
					}
				}
				
				//local person handling
				if($localPerson)
				{
					$match = checkPerson(array('name'=>$author));
					//print_r($match);

					if(!empty($match['localID']))
					{
						//echo 'Person Matched<br>';

						//Use standard name for the local person
						$record[LOCAL_PERSON_FIELD][] = $match['controlName'];

						$record['dc.contributor.author'][$authorPlace] = $match['controlName'];

						$author = $match['controlName'];

						//use local ORCID match (this may overwrite a different ORCID value received from Crossref)
						$orcid = $match['orcid'];

						$facultyLabs[] = checkForFacultyLab($match['localID']);	
						
						$deptIDs = array_merge($deptIDs, checkDeptIDs($match['localID'], $record['dc.date.issued'][0]));
					}
					else
					{
						$record[LOCAL_PERSON_FIELD][] = $author;
					}
				}

				if(!empty($orcid))
				{
					$record['orcid.author'][$authorPlace] = $author . '::' . $orcid;
					$record['dc.identifier.orcid'][$authorPlace] = $orcid;
				}
				else
				{
					//keep author names even when there is no ORCID so that place stays consistent with dc.contributor.author
					$record['orcid.author'][$authorPlace] = $author;
				}
			}

			//remove repeat or empty entries from arrays
			$record['dc.contributor.institution'] = array_unique($record['dc.contributor.institution']);
			$record['dc.contributor.institution'] = array_filter($record['dc.contributor.institution']);
			
			if(isset($record['dc.contributor.department']))
			{
				// check for local department name variants in full affiliation string
				$result = controlLocalAffiliations($record['dc.contributor.department'], $deptIDs, [], []);
				
				//print_r($result);
				
				$record['dc.contributor.department'] = $result['departments'];
				
				//combine department IDs retrieved based on KAUST authors with department IDs retrieved based on department names in full affiliation addresses
				$deptIDs = array_merge($deptIDs, $result['deptIDs']);

				$deptIDs = array_unique($deptIDs);
				$deptIDs = array_filter($deptIDs);
				
				foreach($deptIDs as $deptID)
				{
					$deptName = '';
					
					//echo $deptID.'<br>';

					$deptHandle = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, 'dspace.collection.handle'), array('value'), 'singleValue');

					//echo $deptHandle.'<br>';

					//if the department has a collection in DSpace, the department name will be the collection name
					if(!empty($deptHandle))
					{
						$deptName = getValues($irts, setSourceMetadataQuery('repository', $deptHandle, NULL, 'dspace.name'), array('value'), 'singleValue');
					}
				
					if(empty($deptName))
					{
						$deptName = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, 'local.org.name'), array('value'), 'singleValue');
					}

					$record['dc.contributor.department'][] = $deptName;
				}

				//add faculty lab names to dc.contributor.department
				foreach($facultyLabs as $facultyLab)
				{
					//echo $facultyLab['name'].'<br>';
					
					if(!empty($facultyLab['name']))
					{
						if(!in_array($facultyLab['name'], $record['dc.contributor.department']))
						{
							$record['dc.contributor.department'][] = $facultyLab['name'];
						}
					}
				}

				//remove repeat or empty entries from arrays
				$record['dc.contributor.department'] = array_unique($record['dc.contributor.department']);
				$record['dc.contributor.department'] = array_filter($record['dc.contributor.department']);
			}
			
			if(isset($record[LOCAL_PERSON_FIELD]))
			{
				$record[LOCAL_PERSON_FIELD] = array_unique($record[LOCAL_PERSON_FIELD]);
				$record[LOCAL_PERSON_FIELD] = array_filter($record[LOCAL_PERSON_FIELD]);
			}
		}

		return $record;
	}