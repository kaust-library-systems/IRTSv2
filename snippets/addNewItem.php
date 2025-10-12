<?php
	echo '<div class="container">';
	if(isset($_POST['addItem']))
	{
		$report = '';
		if(!empty($_POST['doi']))
		{
			$dois = explode(',', $_POST['doi']);

			foreach($dois as $doi)
			{
				$report .= '<br>addNewItem - DOI: '.$doi.'<br>';
				
				if(identifyRegistrationAgencyForDOI($doi, $report)==='crossref')
				{
					$sourceData = retrieveCrossrefMetadataByDOI($doi, $report);

					if(!empty($sourceData))
					{
						$result = processCrossrefRecord($sourceData, $report);

						$report .= ' - Crossref record status: '.$result['recordType'].'<br>';
						
						//check for existing entries and add to IRTS as new entry if none found
						$result = addToProcess('crossref', $doi, 'dc.identifier.doi', FALSE, 'Added manually');

						if($result['status'] === 'inProcess')
						{
							$idInIRTS = $result['idInIRTS'];
							
							$report .= " - New entry made in IRTS at: <a href=reviewCenter.php?formType=processNew&itemType=".str_replace(' ', '+', getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.type'), array('value'), 'singleValue'))."&harvestBasis=notFunding&page=0&idInIRTS=$idInIRTS>$idInIRTS</a><br>";
						}
						else
						{
							$report .= ' - Matching record(s) exist. New entry was not created.<br>';
							
							$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $report, 'irts');

							$report .= ' - Matching record(s) in IRTS: <br>';
							
							foreach($existingRecords as $existingID)
							{			
								$report .= '<div class="col-sm-12 border border-dark rounded">
									<br>Type: '.getValues($irts, setSourceMetadataQuery('irts', $existingID, NULL, 'dc.type'), array('value'), 'singleValue').'
									<br>Title: '.getValues($irts, "SELECT  `value`FROM `metadata` WHERE `idInSource` = '$doi' AND `parentRowID` IS NULL AND `field` = 'dc.title' AND `deleted` IS NULL" , array('value'), 'singleValue')."
									<br>IRTS ID: <a href=reviewCenter.php?formType=processNew&itemType=".str_replace(' ', '+',getValues($irts, setSourceMetadataQuery('irts', $existingID, NULL, 'dc.type'), array('value'), 'singleValue'))."&harvestBasis=notFunding&page=0&idInIRTS=$existingID>$existingID</a><br><br></div>";
							}
						}
					}
				}
			}
		}
		elseif(!empty($_POST['arxivID']))
		{
			$arxivIDs = explode(',', $_POST['arxivID']);

			foreach($arxivIDs as $arxivID)
			{
				$report .= '<br>addNewItem - arxivID: '.$arxivID.'<br>';

				$xml = retrieveArxivMetadata('arxivID', $arxivID);

				foreach($xml->entry as $item)
				{
					$result = processArxivRecord($item);
					
					$recordType = $result['recordType'];

					$report .= '<br> - '.$recordType.'<br>';

					//check for existing entries and add to IRTS as new entry if none found
					$result = addToProcess('arxiv', $arxivID, 'dc.identifier.arxivid', TRUE, 'Added manually');
					
					if($result['status'] === 'inProcess')
					{
						$idInIRTS = $result['idInIRTS'];
						
						$report .= " - New entry made in IRTS at: <a href=reviewCenter.php?formType=processNew&itemType=Preprint&harvestBasis=notFunding&page=0&idInIRTS=$idInIRTS>$idInIRTS</a><br>";
					}
				}
			}
		}
		elseif(!empty($_POST['handle'])||!empty($_POST['itemUUID']))
		{
			if(!empty($_POST['handle']))
			{
				$handle = $_POST['handle'];
				$report .= '<br>addNewItem - Handle: '.$handle.'<br>';
				$response = dspaceGetItemByHandle($handle);
			}
			elseif(!empty($_POST['itemUUID']))		
			{
				$itemUUID = $_POST['itemUUID'];
				$report .= '<br>addNewItem - Item UUID: '.$itemUUID.'<br>';
				$response = dspaceGetItem($itemUUID);
			}
			//check  if the response is successfull	
			if($response['status'] == 'success')
			{
				$item = json_decode($response['body'], TRUE);
				
				$itemID = $item['id'];

				//save the sourceData in the database
				$result = saveSourceData($irts, 'dspace', $itemID, $response['body'], 'JSON');

				$recordType = $result['recordType'];
					
				//process item
				$result = processDspaceRecord($response['body']);
					
				$record = $result['record'];

				$message .= $result['report'];
					
				//save it in the database
				$result = saveValues('dspace', $itemID, $record, NULL);

				$handle = $record['dspace.handle'][0]['value'];
				$withdrawn = $record['dspace.withdrawn'][0]['value'];
				$discoverable = $record['dspace.discoverable'][0]['value'];

				if($discoverable && !$withdrawn)
				{
					$existingFieldsToIgnore = 
					[
						'dspace.date.modified',
						'dspace.community.handle',
						'dspace.collection.handle',
						'dspace.bundle.name',
						'dspace.record.visibility'
					];
						
					$result = saveValues('repository', $handle, $record, NULL, $existingFieldsToIgnore);

					$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');

					//check for existing entries and add to IRTS as new entry if none found
					$result = addToProcess('repository', $handle, 'dspace.handle', TRUE, 'Added manually');
				}
				else
				{
					$type = getValues($irts, setSourceMetadataQuery('dspace', $itemID, NULL, 'dc.type'), array('value'), 'singleValue');

					//check for existing entries and add to IRTS as new entry if none found
					$result = addToProcess('dspace', $itemID, 'dspace.uuid', TRUE, 'Added manually');
				}					
					
				if($result['status'] === 'inProcess')
				{
					$idInIRTS = $result['idInIRTS'];
						
					$report .= " - New entry made in IRTS at: <a href=reviewCenter.php?formType=processNew&itemType=".str_replace(' ', '+', $type)."&idInIRTS=$idInIRTS>$idInIRTS</a><br>";
				}
			}
			else
			{
				print_r($dspaceObject);
			}
		}
		
		elseif(!empty($_POST['blank']))
		{
			$type = $_POST['blank'];
			
			$source = 'irts';
			
			$idInIRTS = generateNewID($source);
			
			$result = saveValue('irts', $idInIRTS, 'irts.source', 1, $source, NULL);
			
			$parentRowID =  $result['rowID'];
				
			$result = saveValue('irts', $idInIRTS, 'irts.idInSource', 1, $idInIRTS, $parentRowID);

			$result = saveValue('irts', $idInIRTS, 'dc.type', 1, $type, NULL);

			$result = saveValue('irts', $idInIRTS, 'irts.status', 1, 'inProcess', NULL);
			
			$result = saveValue('irts', $idInIRTS, 'irts.harvest.basis', 1, 'Added manually', NULL);

			$report .= " - New entry made in IRTS at: <a href=reviewCenter.php?formType=processNew&itemType=".str_replace(' ', '+', $type)."&idInIRTS=$idInIRTS>$idInIRTS</a><br>";
		}

		if(isset($report))
		{
			echo $report;
		}
		else
		{
			echo 'no IDs were entered';
		}

		echo '<hr><br><a href="reviewCenter.php?formType=addNewItem" type="button" class="btn btn-primary">Add Another Item</a><a href="reviewCenter.php" type="button" class="btn btn-primary">Return to Review Center</a>';
	}
	else //display form
	{
		echo 'Retrieve metadata and add record to process for a given ID , or create a blank record for a given item type.<br><hr>
			<form method="post" action="reviewCenter.php?formType=addNewItem">
				<div class="form-group">
				  <label for="doi">DOI:</label>
				  <textarea placeholder="10.xxxxx/xxxxxxx" class="form-control" rows="1" name="doi"></textarea>
				</div>
				<div class="form-group">
				  <label for="arxivID">arXiv ID:</label>
				  <textarea class="form-control" rows="1" name="arxivID"></textarea>
				</div>
				<div class="form-group">
				  <label for="handle">Handle:</label>
				  <textarea placeholder="10754/xxxxxx" class="form-control" rows="1" name="handle"></textarea>
				</div>
				<div class="form-group">
				  <label for="blank">Create blank record of the selected item type: </label></br>';
				
				$templates = getValues($irts, "SELECT DISTINCT `idInSource`  FROM `metadata` WHERE `source` LIKE 'irts' AND `idInSource` LIKE 'itemType_%' AND `deleted` IS NULL", array('idInSource'));
			
				echo '<select name="blank">';
				
				echo '<option value=""></option>';
							
				foreach($templates as $template)
				{
					$template = str_replace('itemType_', '', $template);
					
					echo '<option value="'.$template.'">'.$template.'</option>';
				}

				echo '</select>';
				
				echo '</div><input class="btn btn-primary" type="submit" name="addItem" value="Add Item Record for Processing"></input>
			</form>';
	}
	echo '</div>';
?>
