<?php	
	//Define function to update repository records based on information from the linked arXiv record
	function autoUpdateFromArxiv($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//get handles and arXiv IDs for records with comments indicating they have been withdrawn from arXiv
		$items = getValues($irts, "SELECT DISTINCT `idInSource`, `value` arxivID FROM `metadata` 
			WHERE `source` LIKE 'repository' 
			AND `field` LIKE 'dc.identifier.arxivid'
			AND `value` IN (
				SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'arxiv' 
					AND `field` LIKE 'dc.description' 
					AND `value` LIKE '%withdraw%'
					AND `deleted` IS NULL
			)
			AND `idInSource` NOT IN (
				SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'arxiv.status' 
					AND `value` LIKE 'withdrawn'
					AND `deleted` IS NULL
			)
			AND `deleted` IS NULL", 
			array('idInSource','arxivID'), 
			'arrayOfValues');
				
		foreach($items as $item)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$handle = $item['idInSource'];
			
			$arxivID = $item['arxivID'];

			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL.'-- '.$arxivID.PHP_EOL;

			$newMetadata = array('arxiv.status' => array('withdrawn'));

			$response = dspacePrepareAndApplyPatchToItem($handle, $newMetadata, __FUNCTION__);

			$recordTypeCounts[$response['status']]++;

			$itemReport .= $response['report'];

			$itemReport .= '-- '.$response['status'].PHP_EOL;

			$errors = array_merge($errors, $response['errors']);

			$report .= $itemReport;

			echo $itemReport;
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
