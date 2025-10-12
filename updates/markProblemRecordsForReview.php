<?php
	//Define function to mark problem records with notes for review
	function markProblemRecordsForReview($report, $errors, $recordTypeCounts)
	{
		global $irts, $repository;

		//include problemTypes
		include_once 'snippets/problemTypes.php';

		foreach($problemTypes as $problemType => $problemTypeData)
		{
			$idsToMark = getValues($repository, $problemTypeData['query'], array('ID in IRTS'));

			if(count($idsToMark)===0) {
				$report .= 'No records found of type: '.$problemType;
			}
			else {
				$report .= count($idsToMark).' records found of type: '.$problemType.PHP_EOL;

				foreach($idsToMark as $idInIRTS)
				{
					$recordTypeCounts['all']++;
					
					$report .= PHP_EOL.$idInIRTS.PHP_EOL;

					//check if already marked as problem
					$problemStatus = getValues(
						$irts, 
						"SELECT `value` FROM `metadata` 
							WHERE `source` LIKE 'irts' 
							AND `idInSource` LIKE '$idInIRTS'
							AND `field` LIKE 'irts.note' 
							AND `value` LIKE '$problemType'
							AND `deleted` IS NULL", 
						array('value'), 
						'singleValue'
					);

					if(empty($problemStatus))
					{
						//mark as problem by saving note
						$result = saveValue('irts', $idInIRTS, 'irts.note', 0, $problemType, NULL);

						$report .= '- Marked as problem: '.$idInIRTS.PHP_EOL;

						$recordTypeCounts['new']++;
					}
					else
					{
						$report .= '- Already marked with problem note: '.$problemStatus;

						$recordTypeCounts['unchanged']++;
					}
				}
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
