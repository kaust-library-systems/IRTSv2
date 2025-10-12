<?php
	//Define function to record list of pairs of possible duplicates
	function markPossibleDuplicates($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$duplicateTypes = array(
			//Journal always has two DOIs for each article
			'angewandteChemie'=>
				array(
					'description' => 'Two Angewandte Chemie journal article records, one with ange DOI and one with anie DOI',
					'duplicateValuesQuery' => 
						"SELECT DISTINCT value
						FROM `metadata` anie
						WHERE anie.`source` LIKE 'repository' 
						AND anie.`field` LIKE 'dc.identifier.doi' 
						AND anie.`value` LIKE '10.1002/anie%'
						AND anie.`deleted` IS NULL
						AND EXISTS (
							SELECT value FROM metadata
							WHERE source = 'repository'
							AND idInSource != anie.idInSource
							AND `field` LIKE 'dc.identifier.doi' 
							AND `value` LIKE REPLACE(anie.value, 'anie', 'ange')
							AND deleted IS NULL
						)",
					'possibleDuplicateItemsQuery' => 
						"SELECT DISTINCT idInSource, added
						FROM `metadata`
						WHERE `source` LIKE 'repository' 
						AND `field` LIKE 'dc.identifier.doi' 
						AND (
							`value` LIKE '{{value}}'
							OR
							`value` LIKE REPLACE('{{value}}', 'anie', 'ange')
						)
						AND `deleted` IS NULL
						ORDER BY idInSource DESC"
				),
			'id'=>
				array(
					'description' => 'Two records with the same DOI or arXiv ID',
					'duplicateValuesQuery' => 
						"SELECT value, COUNT(DISTINCT `idInSource`) duplicateCount FROM metadata 
						WHERE source='repository'
						AND `parentRowID` IS NULL
						AND field IN ('dc.identifier.arxivid', 'dc.identifier.doi')
						AND deleted IS NULL
						GROUP BY `value` HAVING duplicateCount > 1  
						ORDER BY duplicateCount DESC",
					'possibleDuplicateItemsQuery' => 
						"SELECT DISTINCT idInSource, added
						FROM `metadata`
						WHERE source='repository'
						AND `field` IN ('dc.identifier.arxivid', 'dc.identifier.doi')
						AND `value` LIKE '{{value}}'
						AND deleted IS NULL
						ORDER BY idInSource DESC"
				),
			'title'=>
				array(
					'description' => 'Two records with the same title and type',
					'duplicateValuesQuery' => 
						"SELECT r.value, COUNT(DISTINCT r.`idInSource`) duplicateCount FROM metadata r
							WHERE r.source='repository'
							AND r.`parentRowID` IS NULL
							AND r.field IN ('dc.title')
							AND r.deleted IS NULL
							AND r.idInSource IN (
								SELECT `idInSource` FROM `metadata` 
								WHERE source = 'repository' 
								AND field = 'dspace.community.handle' 
								AND value IN ('10754/324602')
								AND `deleted` IS NULL  
							)
							AND r.idInSource IN (
								SELECT `idInSource` FROM `metadata` 
								WHERE source = 'repository' 
								AND field = 'dc.type' 
								AND value IN ('Conference Paper')
								AND `deleted` IS NULL  
							)
							AND NOT EXISTS (
								SELECT value FROM metadata
								WHERE source = 'repository'
								AND idInSource = r.idInSource
								AND field IN('dc.identifier.doi')
								AND deleted IS NULL
							)
							GROUP BY `value` HAVING duplicateCount > 1  
							ORDER BY duplicateCount DESC",
					'possibleDuplicateItemsQuery' => 
						"SELECT DISTINCT r.idInSource, r.added
						FROM `metadata` r
						WHERE r.source='repository'
						AND r.`field` IN ('dc.title')
						AND r.`value` LIKE '{{value}}'
						AND r.deleted IS NULL
						AND r.idInSource IN (
							SELECT `idInSource` FROM `metadata` 
							WHERE source = 'repository' 
							AND field = 'dspace.community.handle' 
							AND value IN ('10754/324602')
							AND `deleted` IS NULL  
						)
						AND r.idInSource IN (
							SELECT `idInSource` FROM `metadata` 
							WHERE source = 'repository' 
							AND field = 'dc.type' 
							AND value IN ('Conference Paper')
							AND `deleted` IS NULL  
						)
						AND NOT EXISTS (
							SELECT value FROM metadata
							WHERE source = 'repository'
							AND idInSource = r.idInSource
							AND field IN('dc.identifier.doi')
							AND deleted IS NULL
						)
						ORDER BY idInSource DESC"
				)
		);

		foreach($duplicateTypes as $duplicateType => $duplicateTypeData)
		{
			$values = getValues($irts, $duplicateTypeData['duplicateValuesQuery'], array('value'));

			if(count($values)===0) {
				$report .= 'No duplicates found of type: '.$duplicateTypeData['description'];
			}
			else {
				$report .= count($values).' possible duplicates found of type: '.$duplicateTypeData['description'].PHP_EOL;

				foreach($values as $value)
				{
					$recordTypeCounts['all']++;
					
					$report .= PHP_EOL.$value.PHP_EOL;

					$handles = getValues($irts, str_replace('{{value}}', $value, $duplicateTypeData['possibleDuplicateItemsQuery']), array('idInSource'), 'arrayOfValues');

					//limit to first two handles
					$handles = array_slice($handles, 0, 2);

					$possibleDuplicatePair = implode(':', $handles);

					$report .= 'Possible duplicate pair: '.$possibleDuplicatePair.PHP_EOL;

					//check if already marked as possible duplicate pair 
					$possibleDuplicatePairStatus = getValues(
						$irts, 
						"SELECT value FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '$possibleDuplicatePair'
							AND `field` LIKE 'irts.duplicate.status' 
							AND `deleted` IS NULL", 
						array('value'), 
						'singleValue'
					);

					if(empty($possibleDuplicatePairStatus))
					{
						/* //mark as possible duplicate pair by saving status
						$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.status', 0, 'Possible Duplicates to Check', NULL);

						//save duplicate type description
						$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.type', 0, $duplicateTypeData['description'], NULL); */

						$report .= '- Marked as possible duplicate pair: '.$possibleDuplicatePair.PHP_EOL;

						$recordTypeCounts['new']++;
					}
					else
					{
						$report .= '- Already marked as possible duplicate pair with status: '.$possibleDuplicatePairStatus.PHP_EOL;

						$recordTypeCounts['unchanged']++;
					}
				}
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
