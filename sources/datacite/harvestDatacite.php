<?php
	//Define function to harvest DataCite results
	function harvestDataCite($source)
	{
		global $irts, $newInProcess, $errors;

		$report = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0,'error'=>0,'ignored based on a relation'=>0,'skipped based on type'=>0,'added to harvest based on relation'=>0);

		$replaceStr = array('DOI:', 'http://dx.doi.org/', 'https://doi.org/', 'http://doi.org/');

		$articleDOIs = array();
		$datasetDOIs = array();
		$harvestBases = array();

		if(isset($_GET['harvestType']))
		{
			$harvestType = $_GET['harvestType'];

			if(in_array($harvestType, array('relations','metadata')))
			{
				$report .= 'Harvest type: '.$harvestType.PHP_EOL;

				if(isset($_GET['doi']))
				{
					if($harvestType === 'relations')
					{
						$articleDOIs = array($_GET["doi"]);
					}
					elseif($harvestType === 'metadata')
					{
						$datasetDOIs = array($_GET["doi"]);
					}
				}
				else
				{
					if($harvestType === 'relations')
					{
						$articleDOIs = getValues($irts, "SELECT DISTINCT value FROM `metadata`
							WHERE `source` = 'repository'
							AND `field` = 'dc.identifier.doi'
							AND `idInSource` NOT IN (
								SELECT `idInSource` FROM `metadata`
								WHERE `source` LIKE 'repository'
								AND `field` LIKE 'dspace.collection.handle'
								AND `value` LIKE '10754/581392'
								AND `deleted` IS NULL
							)
							AND `deleted` IS NULL", array('value'));
					}
					elseif($harvestType === 'metadata')
					{
						/* $datasetDOIs = getValues($irts, "SELECT `idInSource` FROM `metadata`
						WHERE `source` = 'datacite'
						AND `idInSource` LIKE '10.5517/ccdc%'
						AND `deleted` IS NULL", array('idInSource')); */

						// get related dataset DOIs from IRTS that have not yet been harvested from DataCite
						$datasetDOIs = getValues($irts, "SELECT `value` FROM `metadata`
							WHERE `source` IN ('irts')
							AND `field` LIKE 'dc.related.datasetDOI'
							AND value NOT IN (
								SELECT `idInSource` FROM sourceData
								WHERE `source` = 'datacite'
								AND deleted IS NULL
							)
							AND `deleted` IS NULL", array('value'));
					}
				}
			}
			elseif($harvestType === 'reharvestAll')
			{
				// get all the article DOIs in the repository and exclude the articles in the "Publications Acknowledging KAUST Support" collection
				$articleDOIs = getValues($irts, "SELECT DISTINCT value FROM `metadata`
				WHERE `source` = 'repository'
				AND `field` = 'dc.identifier.doi'
				AND `idInSource` NOT IN (
					SELECT `idInSource` FROM `metadata`
					WHERE `source` LIKE 'repository'
					AND `field` LIKE 'dspace.collection.handle'
					AND `value` LIKE '10754/581392'
					AND `deleted` IS NULL
				)
				AND `deleted` IS NULL", array('value'));

				// get related dataset DOIs from IRTS or the repository (entries in IRTS would have been made during initial processing, while entries in the repository may have been added manually)
				$datasetDOIs = getValues($irts, "SELECT `value` FROM `metadata`
					WHERE `source` IN ('irts','repository')
					AND `field` LIKE 'dc.relation.issupplementedby'
					AND `value` LIKE 'DOI:%'
					AND `deleted` IS NULL", array('value'));
					
				foreach($datasetDOIs as $datasetDOI)
				{
					// clean DOI
					$datasetDOI = str_replace($replaceStr, '', $datasetDOI);
					
					$harvestBases[$datasetDOI] = 'Harvested based on mention in related article data availability statement';
				}
			}
		}
		else
		{
			define('TWO_YEARS_AGO', date('Y', strtotime('-2 years')));
			
			// get DOIs in the repository for papers published in the last 2 years and not checked in the last 3 months, exclude papers in the "Publications Acknowledging KAUST Support" collection
			$articleDOIs = getValues($irts, "SELECT DISTINCT value FROM `metadata`
				WHERE `source` = 'repository'
				AND `field` = 'dc.identifier.doi'
				AND idInSource IN (
					SELECT `idInSource` FROM `metadata`
					WHERE `source` = 'repository'
					AND `field` = 'dc.date.issued'
					AND `value` >= '".TWO_YEARS_AGO."'
					AND `deleted` IS NULL
				)
				AND value NOT IN (
					 SELECT REPLACE(`idInSource`,'doi_','') FROM metadata
					 WHERE `source` = 'irts'
					 AND `field` = 'irts.check.datacite'
					 AND deleted IS NULL
					 AND added >= '".THREE_MONTHS_AGO."'
				)
				AND `idInSource` NOT IN (
					SELECT `idInSource` FROM `metadata`
					WHERE `source` LIKE 'repository'
					AND `field` LIKE 'dspace.collection.handle'
					AND `value` LIKE '10754/581392'
					AND `deleted` IS NULL
				)
				AND `deleted` IS NULL", array('value'));

			/* // get related dataset DOIs from IRTS or the repository (entries in IRTS would have been made during initial processing, while entries in the repository may have been added manually) that have not been checked in the last 3 months
			$datasetDOIs = getValues($irts, "SELECT `value` FROM `metadata`
				WHERE `source` IN ('irts','repository')
				AND `field` LIKE 'dc.relation.issupplementedby'
				AND `value` LIKE 'DOI:%'
				AND SUBSTRING_INDEX(value,'DOI:',-1) NOT IN (
					SELECT `idInSource` FROM sourceData
					WHERE `source` = 'datacite'
					AND deleted IS NULL
					AND added >= '".THREE_MONTHS_AGO."'
				)
				AND `deleted` IS NULL", array('value')); */

			// get related dataset DOIs from IRTS that have not yet been harvested from DataCite
			$datasetDOIs = getValues($irts, "SELECT `value` FROM `metadata`
				WHERE `source` IN ('irts')
				AND `field` LIKE 'dc.related.datasetDOI'
				AND value NOT IN (
					SELECT `idInSource` FROM sourceData
					WHERE `source` = 'datacite'
					AND deleted IS NULL
				)
				AND `deleted` IS NULL", array('value'));
				
			foreach($datasetDOIs as $datasetDOI)
			{
				// clean DOI
				$datasetDOI = str_replace($replaceStr, '', $datasetDOI);
				
				$harvestBases[$datasetDOI] = 'Harvested based on mention in related article data availability statement';
			}
		}

		$report .= count($articleDOIs).' article DOIs to check: '.PHP_EOL;

		foreach($articleDOIs as $key => $articleDOI)
		{
			$articleDOIReport = $key.') '.$articleDOI.PHP_EOL;
			
			//echo $key.') '.$articleDOI.PHP_EOL;

			$response = queryDatacite($articleDOI, 'relations');

			//2 second pause between requests to avoid rate limiting
			sleep(2);

			// check if the response is as expected
			if($response['status'] === 'success')
			{
				$relatedDatasets = $response['body'];

				$relatedDatasets = json_decode($relatedDatasets, TRUE);

				if(empty($relatedDatasets['data']))
				{
					//This is a secondary query because at the current time some data repositories (namely Dryad) prefix the related article DOIs in their metadata in a way that the format has to be queried separately
					$response = queryDatacite('"doi:'.$articleDOI.'"', 'relations');

					//2 second pause between requests to avoid rate limiting
					sleep(2);

					if($response['status'] === 'success')
					{
						$relatedDatasets = json_decode($response['body'], TRUE);
					}
				}

				if(!empty($relatedDatasets['data']))
				{
					$articleDOIReport .= ' - '.count($relatedDatasets['data']).' related DOIs found:'.PHP_EOL;

					foreach ($relatedDatasets['data'] as $relatedDatasetDOI)
					{
						// clean DOI
						$datasetDOI = str_replace($replaceStr, '', $relatedDatasetDOI['id']);
						
						$articleDOIReport .= '  - '.$datasetDOI.PHP_EOL;

						// add DOI to list of datasetDOIs to harvest metadata for below
						$datasetDOIs[] = $datasetDOI;
						
						$harvestBases[$datasetDOI] = 'Harvested based on querying DataCite by related article DOI';
					}
				}

				//Mark check of article DOI as complete
				$result = saveValue('irts', 'doi_'.$articleDOI, 'irts.check.datacite', 1, 'completed' , NULL);

				if($result['status']==='unchanged')
				{
					update($irts, 'metadata', array("added"), array(date("Y-m-d H:i:s"), $result['rowID']), 'rowID');
				}
			}
			else
			{
				$articleDOIReport .= '- error: '.print_r($response, TRUE).PHP_EOL;
			}

			$report .= $articleDOIReport.PHP_EOL;

			echo $articleDOIReport.PHP_EOL;

			ob_flush();
			flush();
			set_time_limit(0);
		}

		// make sure the dataset DOIs are unique
		$datasetDOIs = array_unique($datasetDOIs);

		$report .= count($datasetDOIs).' dataset DOIs to check: '.PHP_EOL;

		//Use an iterator to allow entries to be added to the list of DOIs to check as they are found
		//This is necessary because additional DOIs may be found in the relations of the dataset DOIs that are not already in the list of DOIs to check
		//This is a workaround for the fact that PHP does not allow you to add to an array while iterating over it
		$datasetDOIsIterator = new ArrayIterator($datasetDOIs);

		foreach($datasetDOIsIterator as $key => $datasetDOI)
		{
			$recordTypeCounts['all']++;
			
			// clean DOI
			$datasetDOI = str_replace($replaceStr, '', $datasetDOI);

			$datasetDOIReport = PHP_EOL.$key.') '.$datasetDOI.PHP_EOL;

			if(!empty($datasetDOI))
			{
				// get the result from the API
				$response = queryDatacite($datasetDOI, 'metadata');

				//2 second pause between requests to avoid rate limiting
				sleep(2);

				if($response['status'] === 'success')
				{
					$recordJSON = $response['body'];
					
					$result = saveSourceData($irts, $source, $datasetDOI, $recordJSON, 'JSON');

					$recordType = $result['recordType'];

					$datasetDOIReport .= ' - '.$source.' status: '.$recordType.PHP_EOL;

					if(!empty($result['report']))
					{
						$datasetDOIReport .= ' - saveSourceData Report: '.$result['report'].PHP_EOL;
					}

					$recordTypeCounts[$recordType]++;

					//convert record to local record array structure
					$record = processDataciteRecord($recordJSON);

					//if there is data
					if(!empty($record))
					{
						// check if the doi has _d, this is used by Figshare for data files that are part of a dataset record referred to by the base DOI
						if(preg_match('/10.6084\/m9.figshare.c(.*)_d(.*)/', $datasetDOI))
						{
							$doiWithoutD = substr($datasetDOI, 0, strpos($datasetDOI, '_d'));

							// add new relation to the metadata
							$record['dc.relation.ispartof'][]['value'] =  'DOI:'.$doiWithoutD;
						}

						// Always check if the DOI of the Dataset is in the DB with the relation "isidenticalto" with another dataset
						$hasIdentical = getValues($irts, "SELECT `idInSource` FROM `metadata`
							WHERE `source` = 'datacite'
							AND `field` LIKE 'dc.relation.isidenticalto'
							AND `value` LIKE 'DOI:".$datasetDOI."'
							AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');

						if(!empty($hasIdentical))
						{
							foreach($hasIdentical as $identicalDOI)
							{
								// add return relation to the dataset_A record
								$record['dc.relation.isidenticalto'][]['value'] =  'DOI:'.$identicalDOI;
							}
						}
						
						// get the article DOIs that are associated with this dataset DOI
						$articleDOIs = getValues($irts, "SELECT `value` FROM `metadata`
							WHERE `idInSource` IN (
								SELECT `idInSource` FROM `metadata`
								WHERE source IN ('irts','repository')
								AND field = 'dc.relation.issupplementedby'
								AND `value` LIKE 'DOI:$datasetDOI'
								AND `deleted` IS NULL
							)
							AND field = 'dc.identifier.doi'
							AND `deleted` IS NULL", array('value'), 'arrayOfValues');

						foreach($articleDOIs as $articleDOI)
						{
							$record['dc.relation.issupplementto'][]['value'] = 'DOI:'.$articleDOI;
						}

						if(isset($record['dc.relation.issupplementto']))
						{
							$uniqueIds = array_unique(array_column($record['dc.relation.issupplementto'], 'value'));
							
							$record['dc.relation.issupplementto'] = array();
							
							foreach($uniqueIds as $uniqueId)
							{
								$record['dc.relation.issupplementto'][]['value'] = $uniqueId;
							}
						}

						$functionReport = saveValues($source, $datasetDOI, $record, NULL);

						// check the dataset relations
						$result = handleDataciteRelations($record, $datasetDOI);

						// check the result based on the relation
						if($result['saveA'])
						{
							if($record['dc.type'][0]['value'] !== 'Data File')
							{
								if(isset($harvestBases[$datasetDOI]))
								{
									$harvestBasis = $harvestBases[$datasetDOI];
								}
								else
								{
									$harvestBasis = 'DOI did not match DOI in harvest bases list';
								}
								
								//check for existing entries and add to IRTS as new entry if none found
								$result = addToProcess($source, $datasetDOI, 'dc.identifier.doi', FALSE, $harvestBasis);

								if($result['status'] === 'inProcess')
								{
									$newInProcess++;
								}

								$datasetDOIReport .= '- IRTS status: '.$result['status'].PHP_EOL;
								
							}
							else
							{
								$recordTypeCounts['skipped based on type']++;
								$datasetDOIReport .= ' - skipped based on type'.PHP_EOL;
							}
						}
						else
						{
							$recordTypeCounts['ignored based on a relation']++;
							$datasetDOIReport .= ' - ignored based on a relation'.PHP_EOL;
						}

						if(!empty($result['getB']))
						{
							foreach($result['getB'] as $doiB)
							{
								if(!in_array($doiB, $datasetDOIsIterator->getArrayCopy()))
								{
									// add DOI to list of datasetDOIs to harvest metadata for below

									$datasetDOIsIterator->append($doiB);
									$recordTypeCounts['added to harvest based on relation']++;
									$report .= ' - '.$doiB.' added to harvest based on relation'.PHP_EOL;
									
									$harvestBases[$doiB] = 'Added to harvest based on relation to another dataset DOI';
								}
							}
						}
					}
					else
					{
						$recordTypeCounts['skipped']++;
					}
				}
				else
				{
						$recordTypeCounts['error']++;
						$datasetDOIReport .= print_r($response, TRUE);
				}
			}

			$report .= $datasetDOIReport.PHP_EOL;

			echo $datasetDOIReport.PHP_EOL;

			ob_flush();
			flush();
			set_time_limit(0);
		}
		//echo $report;

		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
