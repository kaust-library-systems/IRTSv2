<?php
	//Define function to harvest Scopus metadata records
	function harvestScopus($source)
	{
		global $irts, $newInProcess, $errors, $report;

		$report = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'skipped'=>0,'unchanged'=>0);

		$iterationsOfHarvest = 1;

		$entries = array();

		if(isset($_GET['reharvest']))
		{
			if(isset($_GET['year']))
			{
				$year = $_GET['year'];
				
				//Reharvest known EIDs matching a custom query
				$result = $irts->query("SELECT idInSource FROM `metadata` 
				WHERE `source` LIKE 'scopus' 
				AND `field` LIKE 'dc.date.issued'
				AND `value` LIKE '$year%'
				AND `deleted` IS NULL");

				if($result->num_rows!==0)
				{
					while($row = $result->fetch_assoc())
					{
						$eid = $row['idInSource'];

						$xml = queryScopus('eid', $eid);

						$entries = addToScopusList($xml, $entries);
						
						$entries[] = $eid;
					}
				}
			}
			elseif(isset($_GET['eid']))
			{
				$eid = $_GET['eid'];

				$xml = queryScopus('eid', $eid);

				$entries = addToScopusList($xml, $entries);
			}
		}
		else
		{
			//Change this to decide how many of the available updates to run
			while($iterationsOfHarvest <= 3)
			{
				if($iterationsOfHarvest === 1)
				{
					$queryTypes = array('affiliation'=>'Harvested based on affiliation search','funding'=>'Harvested based on funding search');
					
					foreach($queryTypes as $queryType => $harvestBasis)
					{
						//For the daily harvest, we will only check the most recent 200 items
						$xml = queryScopus($queryType, NULL, $recordTypeCounts['all'], 200);
						
						$entries = addToScopusList($xml, $entries, $harvestBasis);	
					}
				}
				elseif($iterationsOfHarvest === 2)
				{
					//Check for DOIs harvested from other sources
					$result = $irts->query("SELECT value FROM metadata WHERE source = 'irts'
					AND field = 'dc.identifier.doi'
					AND idInSource IN (
						SELECT idInSource FROM metadata WHERE source = 'irts'
						AND field = 'irts.status'
						AND value IN ('inProcess')
						AND deleted IS NULL)
					AND value NOT IN (
						SELECT value FROM metadata WHERE source = 'scopus'
						AND field = 'dc.identifier.doi'
						AND deleted IS NULL)
					AND deleted IS NULL");

					if($result->num_rows!==0)
					{
						while($row = $result->fetch_assoc())
						{
							$inProcessDOI = $row['value'];

							$xml = queryScopus('doi', $inProcessDOI);

							$entries = addToScopusList($xml, $entries);
						}
					}
				}
				elseif($iterationsOfHarvest === 3)
				{
					//Check for EIDs without known DOIs
					$result = $irts->query("SELECT idInSource FROM metadata title WHERE title.source = 'scopus'
						AND title.field = 'dc.title'
						AND title.idInSource NOT IN (
							SELECT idInSource FROM metadata WHERE source = 'scopus'
							AND field = 'dc.identifier.doi'
							AND deleted IS NULL)
						AND NOT EXISTS (
							SELECT idInSource FROM metadata WHERE source = 'irts'
							AND idInSource = CONCAT('scopus_',title.idInSource)
							AND deleted IS NULL)
						AND NOT EXISTS (
							SELECT idInSource FROM metadata WHERE source = 'repository'
							AND field = 'dc.title'
							AND value = title.value
							AND deleted IS NULL)");

					if($result->num_rows!==0)
					{
						while($row = $result->fetch_assoc())
						{
							$eid = $row['idInSource'];

							$xml = queryScopus('eid', $eid);

							$entries = addToScopusList($xml, $entries);
						}
					}
				}
				$iterationsOfHarvest++;
			}
		}
		
		//foreach($entries as $eid)
		foreach($entries as $eid => $entry)
		{
			$recordTypeCounts['all']++;
			
			$report .= PHP_EOL.'EID: '.$eid.PHP_EOL;
			
			$harvestBasis = $entry['harvestBasis'];

			$sourceData = retrieveScopusRecord('abstract', 'eid', $eid);

			//print_r($sourceData);

			if(is_string($sourceData))
			{
				if(strpos($sourceData, '<statusCode>RESOURCE_NOT_FOUND</statusCode>') !== FALSE)
				{
					$report .= '- Resource Not Found'.PHP_EOL;

					$recordTypeCounts['skipped']++;
				}
				else
				{
					//Strip namespaces due to problems in accessing elements with namespaces even with xpath, temporary solution?
					$namespaces = array('dc','opensearch','prism','dn','ait','ce','cto','xocs');
					foreach($namespaces as $namespace)
					{
						$sourceData = str_replace('<'.$namespace.':', '<', $sourceData);

						$sourceData = str_replace('</'.$namespace.':', '</', $sourceData);
					}

					$sourceData = simplexml_load_string($sourceData);

					//remove bibliography from saved and processed record
					unset($sourceData->item->bibrecord->tail);

					$result = saveSourceData($irts, $source, $eid, $sourceData->asXML(), 'XML');
					$recordType = $result['recordType'];

					$report .= ' - '.$recordType.PHP_EOL;

					$recordTypeCounts[$recordType]++;

					$record = processScopusRecord($sourceData);

					//print_r($record);

					$functionReport = saveValues($source, $eid, $record, NULL);

					//$report .= $functionReport;
					
					$result = addToProcess('scopus', $eid, 'dc.identifier.eid', TRUE, $harvestBasis);

					if($result['status'] === 'inProcess')
					{
						$newInProcess++;
					}

					$report .= '- IRTS status: '.$result['status'].PHP_EOL;
				}
			}
			else
			{
				$recordTypeCounts['skipped']++;

				$report .= '- Unexpected, non-string response from Scopus API'.PHP_EOL;
			}

			flush();
			set_time_limit(0);
		}

		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
