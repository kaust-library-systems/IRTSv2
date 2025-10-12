<?php
	//Define function to update Pure XML for repository items
	function updatePureXML($report, $errors, $recordTypeCounts)
	{
		global $irts, $report, $errors;

		if(!isset($_GET['skipRepositoryHarvest']))
		{
			if(isset($_GET['type']))
			{
				$types = array($_GET['type']);
			}
			else
			{
				$types = array('Article', 'Book Chapter', 'Conference Paper', 'Dataset', 'Dissertation', 'Thesis');
			}

			//$types = array('All', 'AllETD', 'Article', 'Book_Chapter', 'Conference_Paper', 'Dataset', 'Dissertation', 'Patent', 'Thesis');

			//$types = array('Article');

			$publicationTypes = array('Article', 'Book', 'Book Chapter', 'Conference Paper', 'Patent');
			$etdTypes = array('Dissertation', 'Thesis');

			$publicationExports = array('All'=>array(),'New'=>array(),'Modified'=>array());
			$etdExports = array('All'=>array(),'New'=>array(),'Modified'=>array());

			foreach($types as $typeToCheck)
			{
				$exports = generatePureXML('repository', 'dc.type', $typeToCheck, NULL);

				$report .= ' - '.$typeToCheck.' Pure XML successfully generated'.PHP_EOL;

				if(in_array($typeToCheck, $publicationTypes))
				{
					foreach($exports as $exportType => $exportedIDs)
					{
						$publicationExports[$exportType] = array_merge($publicationExports[$exportType], $exportedIDs);
					}
				}
				elseif(in_array($typeToCheck, $etdTypes))
				{
					foreach($exports as $exportType => $exportedIDs)
					{
						$etdExports[$exportType] = array_merge($etdExports[$exportType], $exportedIDs);
					}
				}
				$recordTypeCounts['all']++;
			}
		}

		if(!isset($_GET['skipMultiTypeEntryUpdate']))
		{
			foreach($publicationExports as $exportType => $exportedIDs)
			{
				$entryID = 'dc.type_All Publications_'.$exportType;
				$recordType = saveSourceData($irts, $report, 'forPure_repositoryExport', $entryID, json_encode(array_unique($exportedIDs)), 'JSON');
				if( $recordType == 'unchanged')
				{
					$irts->query("UPDATE `sourceData` SET `added`='".date("Y-m-d H:i:s")."' WHERE `idInSource`='$entryID' and `source`='forPure' and `deleted` IS NULL");
				}
			}

			foreach($etdExports as $exportType => $exportedIDs)
			{
				$entryID = 'dc.type_All ETDs_'.$exportType;
				$recordType = saveSourceData($irts, $report, 'forPure_repositoryExport', $entryID, json_encode(array_unique($exportedIDs)), 'JSON');
				if( $recordType == 'unchanged')
				{
					$irts->query("UPDATE `sourceData` SET `added`='".date("Y-m-d H:i:s")."' WHERE `idInSource`='$entryID' and `source`='forPure' and `deleted` IS NULL");
				}
			}
		}

		if(!isset($_GET['skipDspaceTransfer']))
		{
			if(isset($_GET['typeToTransferToDSpace']))
			{
				$typeToTransferToDSpace = $_GET['typeToTransferToDSpace'];
				
				$report .= 'Type to transfer to DSpace: '.$typeToTransferToDSpace.PHP_EOL;
			}
			
			$exportsToDSpace = array(
			'PublicationsNew.xml'=>'dc.type_All Publications_New',
			'PublicationsModified.xml'=>'dc.type_All Publications_Modified',
			'PublicationsNewAndModified.xml'=>'dc.type_All Publications_NewAndModified',
			'ETDs.xml'=>'dc.type_All ETDs_All',
			'ETDsModified.xml'=>'dc.type_All ETDs_Modified',
			'ETDsNew.xml'=>'dc.type_All ETDs_New',
			'ETDsNewAndModified.xml'=>'dc.type_All ETDs_NewAndModified',
			'Datasets.xml'=>'dc.type_Dataset_All',
			'DatasetsModified.xml'=>'dc.type_Dataset_Modified',
			'DatasetsNew.xml'=>'dc.type_Dataset_New',
			'DatasetsNewAndModified.xml'=>'dc.type_Dataset_NewAndModified');

			$token = loginToDSpaceRESTAPI();

			//get the bitsreams for the item
			$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI(PURE_EXPORT_ITEM_ID, $token);

			if(is_string($bitstreams))
			{
				// convert the json to array
				$bitstreams = json_decode($bitstreams, TRUE);

				//Send export files to DSpace
				foreach($exportsToDSpace as $fileName => $exportID)
				{
					$transfer = TRUE;
					
					if(isset($typeToTransferToDSpace))
					{
						if(strpos($fileName, $typeToTransferToDSpace) === FALSE)
						{
							$transfer = FALSE;
						}
					}
					
					if($transfer)
					{
						$file = '/data/www/irts/public_html/upload/'.$fileName;
						retrievePureXMLFromDB($exportID, $file);

						foreach ($bitstreams as $bitstream)
						{
							//check if file has the correct name
							if($bitstream['name'] == $fileName)
							{
								//delete the bistream from Dspace
								deleteBitstreamFromDSpaceRESTAPI($bitstream['id'], $token);
								
								$report .= '- Existing '.$fileName.' file deleted.'.PHP_EOL;
							}
						}

						$response = postBitstreamToDSpaceRESTAPI(PURE_EXPORT_ITEM_ID, $file, $fileName, date("Y-m-d H:i:s"), 'ORIGINAL', $token);
						
						if(is_string($response))
						{
							$report .= '- New '.$fileName.' file uploaded.'.PHP_EOL;
							
							unlink($file);
						}
						else
						{
							$report .= print_r($response, TRUE).PHP_EOL;
						}
					}
				}
			}
			else
			{
				$report .= print_r($response, TRUE).PHP_EOL;
			}
		}

		if(!isset($_GET['skipScopusHarvest']))
		{
			//Check preKAUST publication history for previously unharvested KAUST faculty
			$recordTypeCounts['Faculty History Scopus Harvest - People Count'] = 0;
			$recordTypeCounts['Faculty History Scopus Harvest - Item Count'] = 0;

			//Our Abstract Retrieval X-RateLimit-Limit is 10000 items per week, X-RateLimit-Remaining header tells how much of the quota remains unused each week
			while ($recordTypeCounts['Faculty History Scopus Harvest - Item Count'] < 1000)
			{
				$person = getValues($irts, "SELECT id.idInSource, id.value scopusID, name.value name
					FROM metadata id
					LEFT JOIN metadata name
					ON id.idInSource = name.idInSource
					WHERE id.source = 'local'
					AND id.field = 'dc.identifier.scopusid'
					AND id.idInSource IN (SELECT idInSource
						FROM metadata
						WHERE source = 'local'
						AND field = 'local.person.title'
						AND (
							value LIKE '%prof %'
							OR value LIKE '%prof.%'
							OR value LIKE '%professor%'
						)
						AND value NOT LIKE '%Visiting%'
						AND value NOT LIKE '%Courtesy%'
						AND deleted IS NULL
					)
					AND id.value NOT IN (SELECT SUBSTRING_INDEX(idInSource,'_',-1)
				    FROM `sourceData`
				    WHERE `source` = 'forPure_scopusExport'
				    AND `idInSource` LIKE 'authorID%'
				    AND `deleted` IS NULL
					)
					AND name.source = 'local'
					AND name.field = 'local.person.name'
					AND id.deleted IS NULL
					AND name.deleted IS NULL
					ORDER BY `name` ASC
					LIMIT 1", array('idInSource', 'scopusID', 'name'), 'arrayOfValues');

				if(!empty($person))
				{
					$recordTypeCounts['all']++;
					$recordTypeCounts['Faculty History Scopus Harvest - People Count']++;
					$personReport = $recordTypeCounts['Faculty History Scopus Harvest - People Count'].') '.print_r($person, TRUE).PHP_EOL;

					$personId = $person[0]['idInSource'];

					$scopusID = $person[0]['scopusID'];

					//Check for all dept IDs for a local.person.id (publication date is empty because this is not for a specific publication but for this person in general)
					$deptIDs = checkDeptIDs($personId, '');

					//Get the owner ID (if one of the dept IDs is a division or core lab)
					$ownerID = owner($deptIDs);

					$personReport .= ' - Owner ID: '.$ownerID.PHP_EOL;

					$includedIDs = generatePureXML('scopus', 'authorID', $scopusID, $ownerID);

					$recordTypeCounts['Faculty History Scopus Harvest - Item Count'] += count($includedIDs);

					$personReport .= ' - Running Items Count: '.$recordTypeCounts['Faculty History Scopus Harvest - Item Count'].PHP_EOL;

					echo $personReport;
					$report .= $personReport;
					ob_flush();
					set_time_limit(0);
				}
				else
				{
					$report .= '-- No more faculty Scopus IDs to check for preKAUST publications'.PHP_EOL;
					break;
				}
			}
		}

		echo $report;

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
