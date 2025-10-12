<?php	
	//Define function to update repository record dc.identifier.journal fields to match the journal name on the Crossref record
	function updateRepositoryJournalNamesBasedOnCrossref($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$token = loginToDSpaceRESTAPI();

		//When an item has multiple DOIs, normally the first one is the journal article DOI, in a few cases it is the bioRxiv preprint DOI and we will skip these for now with a plan to fix them later
		$result = $irts->query("SELECT DISTINCT journal.idInSource Handle, doi.value DOI, journal.value `Repository Journal Name`, crossrefJournal.value `Crossref Journal Name` FROM `metadata` journal 
			LEFT JOIN metadata doi USING(idInSource)
			LEFT JOIN metadata crossrefJournal ON crossrefJournal.idInSource = doi.value
			WHERE journal.`source` = 'repository'
			AND journal.`field` = 'dc.identifier.journal'
			AND journal.`value` NOT LIKE 'Accepted%'
			AND journal.`value` NOT IN ('Angewandte Chemie', 'Lab on a Chip')
			AND journal.deleted IS NULL 
			AND journal.idInSource IN (
				SELECT idInSource FROM metadata
				WHERE source = 'repository'
				AND field = 'dc.type'
				AND value = 'Article'
				AND deleted IS NULL
                )
			AND doi.`source` = 'repository'
			AND doi.field = 'dc.identifier.doi'
			AND doi.place = '1'
            AND doi.value NOT LIKE '10.1101%'
			AND doi.deleted IS NULL 
			AND journal.value != crossrefJournal.value
			AND crossrefJournal.source = 'crossref' 
			AND crossrefJournal.field = 'dc.identifier.journal'
            AND crossrefJournal.value NOT LIKE '%.%'
			AND crossrefJournal.deleted IS NULL");

		while($row = $result->fetch_assoc())
		{
			$handle = $row['Handle'];
			
			$report .= 'Handle: '.$handle.PHP_EOL;
			
			$itemID = getValues($irts, "SELECT `value` FROM `metadata` 
				WHERE `source` LIKE 'repository' 
				AND `idInSource` = '$handle'
				AND `field` LIKE 'dc.internalItemId' 
				AND `deleted` IS NULL", array('value'), 'singleValue');
			
			$journal = $row['Crossref Journal Name'];
			
			$report .= 'DSpace Internal Item ID: '.$itemID.PHP_EOL;

			$recordTypeCounts['all']++;

			$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);

			if(is_string($json))
			{
				$metadata = dSpaceJSONtoMetadataArray($json);

				if($metadata['dc.identifier.journal'][0]['value']!==$journal)
				{
					$recordTypeCounts['modified']++;
					
					$report .= 'Existing journal name: '.$metadata['dc.identifier.journal'][0]['value'].PHP_EOL;
					
					$metadata['dc.identifier.journal'][0]['value'] = $journal;
					
					$report .= 'New journal name based on Crossref: '.$metadata['dc.identifier.journal'][0]['value'].PHP_EOL;

					$metadata = appendProvenanceToMetadata($itemID, $metadata, __FUNCTION__);

					$json = prepareItemMetadataAsDSpaceJSON($metadata);
					
					sleep(2);

					$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
				}
				else
				{
					$report .= 'Existing journal name ('.$metadata['dc.identifier.journal'][0]['value'].') matches new journal name based on Crossref ('.$metadata['dc.identifier.journal'][0]['value'].') -- no change was made'.PHP_EOL;
					
					$recordTypeCounts['unchanged']++;
				}
			}
			else
			{
				$recordTypeCounts['skipped']++;
				
				$report .= print_r($json, TRUE);
			}
			
			sleep(10);
			set_time_limit(0);
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
