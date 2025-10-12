<?php
	//Define function to harvest arxiv metadata via REST API
	function harvestArxiv($source, $harvestType) {
		global $irts, $newInProcess, $errors, $report;

		$report = '';

		$errors = array();
		
		$year = date("Y");

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0);

		/* 
		//Google Scholar based harvest
		$max = 50;
		$result = queryGoogleScholar($count, $max, $institutionNames, $fullTextString); 
		*/
		
		//if reharvest requested
		if(isset($_GET['reharvest'])) {
			//if reharvest requested for specific arXiv ID
			if(isset($_GET['arxivID'])) {
				$arxivIDs = [$_GET['arxivID']];
			}
			else{
				//get all arXiv IDs
				$arxivIDs = getValues($irts, "SELECT DISTINCT `value` FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.identifier.arxivid' AND `deleted` IS NULL", array('value'));
			}

			//for each arXiv ID
			foreach($arxivIDs as $arxivID) {
				//get arXiv record metadata
				$xml = retrieveArxivMetadata('arxivID', $arxivID);

				//print_r($xml);

				foreach($xml->entry as $item) {
					$report .= ' -- '.$item->id.PHP_EOL;

					//process arXiv record
					$result = processArxivRecord($item);

					//whether record was modified or not
					$recordType = $result['recordType'];

					//increment record type count
					$recordTypeCounts[$recordType]++;

					$report .= '  -- '.$recordType.PHP_EOL;
				}

				//get arXiv OAI-PMH record
				/* $xml = retrieveArxivMetadata('OAI', $arxivID);

				print_r($xml);

				foreach($xml->GetRecord->record->metadata->children() as $item) {
					$report .= ' -- '.$item->id.PHP_EOL;

					//process arXiv record
					$result = processArxivRecord($item);

					//whether record was modified or not
					$recordType = $result['recordType'];

					//increment record type count
					$recordTypeCounts[$recordType]++;

					$report .= '  -- '.$recordType.PHP_EOL;
				} */

				ob_flush();

				//pause for 3 seconds between requests, per arXiv's rate limiting guidelines
				sleep(3);
			}
		}
		else {		
			//Author name harvest direct from arXiv
			$harvestBasis = 'Harvested based on active faculty member name';
			
			//Get list of active faculty to check
			$persons = getValues($irts, "SELECT DISTINCT m.`idInSource` FROM `metadata` m
						WHERE `source` LIKE 'local'
						AND field = 'local.employment.type'
						AND value LIKE 'Faculty'
						AND deleted IS NULL
						AND parentRowID NOT IN (
							SELECT parentRowID FROM metadata
							WHERE source='local'
							AND idInSource = m.idInSource
							AND field = 'local.date.end'
							AND deleted IS NULL)", array('idInSource'));
				
			foreach($persons as $idInSource) {
				$report .= $idInSource.PHP_EOL;
				
				$name = getValues($irts, "SELECT `value` FROM `metadata` WHERE `source` LIKE 'local' AND `idInSource` LIKE '$idInSource' AND `field` LIKE 'local.person.name'", array('value'), 'singleValue');
				
				$report .= '-- '.$name.PHP_EOL;
				
				if(in_array($name, array('Wang, Peng', 'Wu, Ying', 'Gao, Xin', 'Han, Yu', 'Li, Mo', 'Wang, Di', 'Sun, Ying', 'Zhang, Huabin'))) {
					$report .= ' -- Skipped - Name is too common'.PHP_EOL;
				}
				else {
					$xml = retrieveArxivMetadata('name', $name);

					//check if $xml is boolean
					if(is_bool($xml))
					{
						$report .= ' -- Error retrieving metadata for name: '.$name.PHP_EOL;
					}
					else
					{
						//loop through each entry in the XML response
						foreach($xml->entry as $item) {
							$report .= ' -- '.$item->id.PHP_EOL;
							
							$recordTypeCounts['all']++;
							
							//if the item was published in the current year, process it
							if(strpos($item->published, $year) !== FALSE) {			
								$result = processArxivRecord($item);
								
								$recordType = $result['recordType'];
								
								$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;
								
								$idInSource = $result['idInSource'];
								
								$result = addToProcess('arxiv', $idInSource, 'dc.identifier.arxivid', TRUE, $harvestBasis);
			
								if($result['status'] === 'inProcess')
								{
									$newInProcess++;
								}

								$report .= '- IRTS status: '.$result['status'].PHP_EOL;
							}
							//if the item was not published in the current year, skip it
							else
							{
								$recordType = 'skipped';
							}
							
							$recordTypeCounts[$recordType]++;
							
							$report .= '  -- '.$recordType.PHP_EOL;
						}
					}

					//pause for 3 seconds between requests, per arXiv's rate limiting guidelines
					sleep(3);
				}
			}
		}
		
		$summary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
