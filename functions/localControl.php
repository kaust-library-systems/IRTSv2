<?php
	//Define function to control local person and department values
	function localControl($record)
	{
		global $irts;
		
		$deptCollectionIDs = array();
		
		foreach($record['dc.contributor.author'] as $authorPlace=>$author)
		{
			if(is_int($authorPlace))
			{
				
			}
			
			//echo $author.'<br>';

			$affiliations = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$author), 'dc.contributor.affiliation'), array('value'), 'arrayOfValues');
			
			$orcid = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, array('parentField'=>'dc.contributor.author', 'parentValue'=>$author), 'dc.identifier.orcid'), array('value'), 'singleValue');

			//print_r($affiliations);

			foreach($affiliations as $affiliation)
			{
				//echo $affiliation.'<br>';
				if(institutionNameInString($affiliation))
				{
					//echo 'KAUST affiliated<br>';
					$match = checkPerson(array('name'=>$author));
					if(!empty($match['localID']))
					{
						//echo 'Person Matched<br>';
						$record['kaust.person'][] = $match['controlName'];
						
						//Respect ORCID received from Crossref, otherwise add ORCID from local person match
						if(!empty($orcid))
						{
							$record['dc.contributor.author'][$authorPlace] = $match['controlName'] . '::' . $orcid;
						}
						elseif(!empty($match['orcid']))
						{
							$record['dc.contributor.author'][$authorPlace] = $match['controlName'] . '::' . $match['orcid'];
						}							
						else
						{
							$record['dc.contributor.author'][$authorPlace] = $match['controlName'];
						}

						$deptIDs = checkDeptIDs($match['localID'], $record['dc.date.issued'][0]);
						foreach($deptIDs as $deptID)
						{
							//echo $deptID.'<br>';
							
							$deptHandle = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, 'dspace.collection.handle'), array('value'), 'singleValue');
							
							//echo $deptHandle.'<br>';
							
							if(!empty($deptHandle))
							{
								$collectionID = getValues($irts, setSourceMetadataQuery('dspace', NULL, NULL, 'dspace.collection.handle', $deptHandle), array('idInSource'), 'singleValue');
								
								$deptCollectionIDs[] = str_replace('collection_', '', $collectionID);
								
								//echo $collectionID.'<br>';
								
								$record['dc.contributor.department'][] = getValues($irts, setSourceMetadataQuery('dspace', $collectionID, NULL, 'dspace.collection.name'), array('value'), 'singleValue');
								
								//print_r($record['dc.contributor.department']);
							}
							else
							{
								$record['dc.contributor.department'][] = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, 'local.org.name'), array('value'), 'singleValue');
								//print_r($record['dc.contributor.department']);
							}
						}
					}
					else
					{
						if(!empty($orcid))
						{
							$record['dc.contributor.author'][$authorPlace] = $author . '::' . $orcid;
						}
						
						$record['dc.contributor.department'][] = $affiliation;
						$record['kaust.person'][] = $author;
					}
				}
				else
				{
					if(!empty($orcid))
					{
						$record['dc.contributor.author'][$authorPlace] = $author . '::' . $orcid;
					}
					
					$record['dc.contributor.institution'][] = $affiliation;
				}
			}
		}
		$record['dc.contributor.institution'] = array_unique($record['dc.contributor.institution']);
		$record['dc.contributor.institution'] = array_filter($record['dc.contributor.institution']);

		$record['dc.contributor.department'] = array_unique($record['dc.contributor.department']);
		$record['dc.contributor.department'] = array_filter($record['dc.contributor.department']);
		
		$deptCollectionIDs = array_unique($deptCollectionIDs);
		$deptCollectionIDs = array_filter($deptCollectionIDs);
		
		return array('record'=>$record,'deptCollectionIDs'=>$deptCollectionIDs);
	}	
