<?php	
	//Define function to update repository record dc.identifier.citation fields based on DOI content negotiation
	function autoUpdateCitationField($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//get handles and DOIs of items that have a DOI and no citation
		$items = getValues($irts, "SELECT idInSource, value doi FROM `metadata` 
			WHERE `source` LIKE 'repository' 
			AND `field` LIKE 'dc.identifier.doi' 
			AND `place` = 0
			AND `deleted` IS NULL
			AND (
				`idInSource` IN (
				SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.identifier.citation' 
					AND `value` = 'Array' 
					AND `deleted` IS NULL
				)
				OR
				`idInSource` NOT IN (
				SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.identifier.citation' 
					AND `deleted` IS NULL
				)
			)
			AND `value` IN (
				SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'doi' 
					AND `field` LIKE 'doi.agency.id' 
					AND `value` IN ('datacite','crossref')
					AND `deleted` IS NULL
			)", 
			array('idInSource','doi'), 
			'arrayOfValues');
				
		foreach($items as $item)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$handle = $item['idInSource'];
			$doi = $item['doi'];
			
			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL.'-- '.$doi.PHP_EOL;
			
			//get citation from DOI content negotiation
			$response = getCitationByDOI($doi);

			if($response['status'] === 'success')
			{
				$citation = trim($response['body']);

				if(!empty($citation)&&is_string($citation)&&$citation!=='Array')
				{
					// remove markup tags
					$citation = standardizeTheUseOfTags($citation, TRUE);

					$itemReport .= '-- New citation = '.$citation.PHP_EOL;

					$newMetadata = array('dc.identifier.citation' => array($citation));

					$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, __FUNCTION__);

					$recordTypeCounts[$response['status']]++;

					$itemReport .= $response['report'];

					$itemReport .= '-- '.$response['status'].PHP_EOL;

					$errors = array_merge($errors, $response['errors']);
				}
				else
				{
					$itemReport .= '-- skipped, empty citation'.PHP_EOL;
					$recordTypeCounts['skipped']++;
				}
			}
			else
			{
				$itemReport .= '-- Failed to get citation for DOI: '.$doi.PHP_EOL;
				$errors[] = 'Failed to get citation for DOI: '.$doi;
				$recordTypeCounts['failed']++;
			}

			$report .= $itemReport;

			echo $itemReport;
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
