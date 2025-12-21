<?php
	//Define function to harvest Semantic Scholar metadata via REST API
	function harvestSemanticScholar($source)
	{
		global $irts, $newInProcess, $errors, $report;

		$report = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'skipped'=>0);

		//Get list of ids to check
		/* $ids = getValues($irts, "SELECT DISTINCT `value` FROM `metadata` 
		WHERE `source` LIKE 'repository' 
		AND `field` IN ('dc.identifier.doi','dc.identifier.arxivid')
		LIMIT 10", array('value'), 'arrayOfValues'); */

		$dois = getValues($irts, "SELECT DISTINCT `value` FROM `metadata` 
			WHERE `source` LIKE 'repository' 
			AND `field` IN ('dc.identifier.doi')", array('value'), 'arrayOfValues');

		foreach($dois as $doi)
		{
			$ids[] = 'DOI:'.$doi;
		}

		$arxivIDs = getValues($irts, "SELECT DISTINCT `value` FROM `metadata` 
			WHERE `source` LIKE 'repository'
			AND `field` IN ('dc.identifier.arxivid')", array('value'), 'arrayOfValues');

		foreach($arxivIDs as $arxivID)
		{
			$ids[] = 'arXiv:'.$arxivID;
		}

		//chunk ids into groups of 500
		$chunks = array_chunk($ids, 500);
			
		//loop through chunks
		foreach($chunks as $chunk)
		{
			$paperIDsJSON = json_encode(array('ids'=>$chunk));

			//echo 'paperIDsJSON: '.print_r($paperIDsJSON, TRUE).PHP_EOL;
			
			//$commaSeparatedFields = 'authors,citationCount,externalIds,journal,publicationVenue,title,url,venue,year';
			$commaSeparatedFields = 'citationCount,externalIds,title,url,year';
			
			$response = getBatchOfSemanticScholarRecords($paperIDsJSON, $commaSeparatedFields);
			
			if($response['status'] === 'success')
			{
				//print_r($response);
				
				$items = json_decode($response['body'], TRUE);

				foreach($items as $item)
				{
					if(isset($item['paperId']))
					{
						$recordTypeCounts['all']++;
					
						$report .= $item['paperId'].PHP_EOL;
						
						$result = processSemanticScholarRecord($item);
						
						$recordType = $result['recordType'];
						
						$idInSource = $result['idInSource'];
						
						$recordTypeCounts[$recordType]++;
						
						$report .= '-- '.$recordType.PHP_EOL;
					}
					else
					{
						//$errors[] = 'Error: '.print_r($item, TRUE);
					}					
				}
			}
			else
			{
				$errors[] = 'Error: '.print_r($response, TRUE);

				break;
			}
			
			sleep(5);
		}
		
		$summary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
