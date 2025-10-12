<?php
	//Define function to harvest Google Scholar results
	function harvestGoogleScholar($source)
	{
		global $irts, $newInProcess, $errors;

		$report = '';

		$errors = array();

		$records = array();
		
		$harvestBasis = 'Harvested based on full text search in Google Scholar';

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0, 'check for DOI'=>0);

		$fullTextStrings = array('-site:34.254.210.247 -site:archive.kaust.edu.sa -site:repository.kaust.edu.sa -source:springer -source:ieee -source:elsevier');
		//$fullTextStrings = array('source:arxiv');
		//$fullTextStrings = array('source:springer');
		//$fullTextStrings = array('source:elsevier');
		//$fullTextStrings = array('source:ieee', 'source:elsevier');
		//$fullTextStrings = array('site:pubs.rsc.org');
		//$fullTextStrings = array('source:ACS');
		//$fullTextStrings = array('source:Wiley');
		//$fullTextStrings = array('-source:arxiv -site:pubs.rsc.org -site:repository.kaust.edu.sa -source:ACS -source:wiley -source:springer -source:ieee -source:elsevier');

		foreach($fullTextStrings as $fullTextString)
		{
			$max = 20;

			while($recordTypeCounts['all'] < $max)
			{
				$queryResult = queryGoogleScholar($recordTypeCounts['all'], $fullTextString);

				$report .= $queryResult['url'].PHP_EOL;

				foreach($queryResult['result']->getElementsByTagName('div') as $div)
				{
					$class = $div->getAttribute('class');

					if($class === 'gs_r gs_or gs_scl')
					{
						$recordTypeCounts['all']++;

						$record = processGoogleScholarRecord($div);

						$report .= print_r($record, TRUE);

						if(!empty($record['googleScholar.cluster.id'][0]['value']))
						{
							$clusterID = $record['googleScholar.cluster.id'][0]['value'];

							$report .= '-- '.$clusterID.PHP_EOL;

							$result = saveSourceData($irts, $source, $clusterID, $div->ownerDocument->saveHTML($div), 'HTML');
							$recordType = $result['recordType'];

							$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;

							$recordTypeCounts[$recordType]++;

							$functionReport = saveValues($source, $clusterID, $record, NULL);

							$records[$clusterID] = $record;
						}
					}
					ob_flush();
				}
				$sleepInterval = rand(300,900);
				echo 'Now sleep for '.$sleepInterval.' seconds' . PHP_EOL;
				ob_flush();
				flush();
				set_time_limit(0);
				//Insert pauses into the harvest so that Google Scholar does not mistake us for a machine recursively querying their site
				sleep($sleepInterval);
			}
		}

		// get records that have not been recently rechecked and which do not have a DOI identified
		$clusterIDs = getValues($irts, "SELECT idInSource FROM `metadata` WHERE `source` LIKE 'googleScholar'
			AND `field` LIKE 'dc.title'
			AND deleted IS NULL
			AND idInSource NOT IN (
			    SELECT idInSource  FROM `metadata`
			    WHERE `source` LIKE 'googleScholar'
			    AND `field` LIKE 'dc.identifier.doi'
					AND deleted IS NULL
			)
			AND idInSource NOT IN (
				 SELECT REPLACE(`idInSource`,'googleScholar_','') FROM metadata
				 WHERE `source` = 'irts'
				 AND `field` = 'irts.check.googleScholar'
				 AND deleted IS NULL
				 AND added >= '".THREE_MONTHS_AGO."'
			)", array('idInSource'));

		foreach($clusterIDs as $clusterID)
		{
			$report .= '-- '.$clusterID.PHP_EOL;

			$recordTypeCounts['check for DOI']++;

			$sourceData = getValues($irts, "SELECT sourceData FROM `sourceData`
					WHERE `source` LIKE 'googleScholar'
					AND `idInSource` LIKE '$clusterID'
					AND `deleted` IS NULL", array('sourceData'), 'singleValue');

			if(!empty($sourceData))
			{
				$div = new DOMDocument();
				libxml_use_internal_errors(true);
				$div->loadHTML($sourceData);

				$record = processGoogleScholarRecord($div);

				$report .= print_r($record, TRUE).PHP_EOL;

				$functionReport = saveValues($source, $clusterID, $record, NULL);

				$records[$clusterID] = $record;
			}
		}

		foreach($records as $clusterID => $record)
		{
			echo $clusterID.': '.print_r($record).PHP_EOL;

			if(!empty($record['dc.identifier.doi'][0]['value']))
			{
				$doi = $record['dc.identifier.doi'][0]['value'];

				if(identifyRegistrationAgencyForDOI($doi, $report)==='crossref')
				{
					$recordTypeCounts['all']++;

					$sourceData = retrieveCrossrefMetadataByDOI($doi, $report);

					if(!empty($sourceData))
					{
						$report .= '-- '.$doi.PHP_EOL;

						$recordType = processCrossrefRecord($sourceData, $report);
						
						$recordTypeCounts[$recordType]++;

						$report .= '-- Crossref status: '.$recordType.PHP_EOL;

						$result = addToProcess('crossref', $doi, 'dc.identifier.doi', FALSE, $harvestBasis);
					}
				}
			}
			elseif(!empty($record['dc.identifier.arxivid'][0]['value']))
			{
				$arxivID = $record['dc.identifier.arxivid'][0]['value'];

				$xml = retrieveArxivMetadata('arxivID', $arxivID);

				foreach($xml->entry as $item)
				{
					$report .= '-- '.$item->id.PHP_EOL;

					$result = processArxivRecord($item);

					$recordType = $result['recordType'];

					$idInSource = $result['idInSource'];
					
					$recordTypeCounts[$recordType]++;

					$report .= '-- arXiv status: '.$recordType.PHP_EOL;

					$result = addToProcess('arxiv', $idInSource, 'dc.identifier.arxivid', FALSE, $harvestBasis);
				}
			}
			else
			{
				$status = 'unknown';
				
				if(!empty($record['dc.title'][0]['value']))
				{
					$title = $record['dc.title'][0]['value'];
					
					//Check repository for existing record with even partial title match
					$existingRecords = checkForExistingRecords($title, 'dc.title', $report);
					
					if(empty($existingRecords))
					{
						//Check repository for existing record with even partial title match
						$existingRecords = checkForExistingRecords($title, 'dc.title', $report, 'irts');
						
						if(empty($existingRecords))
						{
							//Only add to process if there is no similar title match in either irts or the repository
							$result = addToProcess('googleScholar', $clusterID, 'googleScholar.cluster.id', FALSE, $harvestBasis);
						}
					}
				}
			}			
			
			$report .= '- IRTS status: '.$result['status'].PHP_EOL;
			if($result['status'] === 'inProcess')
			{
				$newInProcess++;
			}
			
			//Mark check for DOI as complete
			$result = saveValue('irts', 'googleScholar_'.$clusterID, 'irts.check.googleScholar', 1, 'completed', NULL);

			if($result['status']==='unchanged')
			{
				update($irts, 'metadata', array("added"), array(date("Y-m-d H:i:s"), $result['rowID']), 'rowID');
			}
		}

		print_r($errors);

		$summary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
