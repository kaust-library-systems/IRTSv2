<?php
	//Define function to update the relationships information in the repository
	function updateRepositoryRelations($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$token = loginToDSpaceRESTAPI();

		//Get item ID or list of item IDs to check
		if(isset($_GET['handle']))
		{
			$itemIDs = getValues($irts, "SELECT DISTINCT value FROM `metadata` 
				WHERE `source` LIKE 'repository' 
				AND idInSource LIKE '".$_GET['handle']."'
				AND `field` LIKE 'dc.internalItemId' 
				AND `deleted` IS NULL", array('value'), 'arrayOfValues');
		}
		else
		{
			$itemIDs = getValues($irts, "SELECT DISTINCT idInSource FROM `metadata` 
				WHERE `source` LIKE 'dspace' 
				AND `field` LIKE 'dc.relation.isSupplementTo' 
				AND `added` LIKE '2021-03-02%'
				AND idInSource NOT IN (SELECT DISTINCT value FROM `metadata` 
					WHERE `source` LIKE 'repository' 
					AND `field` LIKE 'dc.internalItemId' 
					AND idInSource IN (SELECT DISTINCT idInSource FROM `metadata` 
						WHERE `source` LIKE 'repository' 
						AND `field` LIKE 'display.relations' 
						AND `deleted` IS NULL)
					AND `deleted` IS NULL)
				AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
		}

		foreach($itemIDs as $itemID)
		{
			$recordTypeCounts['all']++;

			$report .= $itemID.PHP_EOL;
			echo $itemID.PHP_EOL;

			$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);
			if(is_string($json))
			{
				$record = json_decode($json, TRUE);

				$record = dSpaceMetadataToArray($record);
				
				$result = setDisplayRelationsField($record);
							
				if(in_array($result['status'], array('new','changed')))
				{
					//update the metadata for the related record in DSpace
					$record = $result['record'];
					
					$record = appendProvenanceToMetadata($itemID, $record, __FUNCTION__);

					$json = prepareItemMetadataAsDSpaceJSON($record);
					
					sleep(5);

					$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
					
					$recordTypeCounts['modified']++;

					echo ' - modified'.PHP_EOL;
					$report .= ' - modified'.PHP_EOL;
				}
				else
				{
					$recordTypeCounts['unchanged']++;

					echo ' - unchanged'.PHP_EOL;
					$report .= ' - unchanged'.PHP_EOL;
				}
				
				//set inverse relationships on any related items
				$result = setInverseRelations($itemID);
				
				if(!empty($result))
				{
					$report .= PHP_EOL.' - Set Inverse Relations Result: '.$result.PHP_EOL;
				}

			}
			else
			{
				echo $itemID.') Skipped: '.print_r($json, TRUE).PHP_EOL;
				$recordTypeCounts['skipped']++;
			}
			sleep(5);
			ob_flush();
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
