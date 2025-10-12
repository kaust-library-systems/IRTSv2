<?php
	echo '<div class="container">';
	if(isset($_POST['editItem']))
	{
		$report = '';
		
		$identifiers = [];
		
		if(!empty($_POST['doi']))
		{
			$identifiers[trim($_POST['doi'])] = 'dc.identifier.doi';
		}
		elseif(!empty($_POST['arxivID']))
		{
			$identifiers[trim($_POST['arxivID'])] = 'dc.identifier.arxivid';
		}
		elseif(!empty($_POST['handle']))
		{
			$handle = trim($_POST['handle']);

			//make sure we have the current metadata for the item (and for its current version)
			$response = dspaceGetItemByHandle($handle);

			if($response['status'] == 'success')
			{
				$item = json_decode($response['body'], TRUE);
			
				$itemID = $item['id'];

				//use handle from response as handle entered in form may have been in the form of the full URL
				$handle = $item['handle'];

				//save the sourceData in the database
				$result = saveSourceData($irts, 'dspace', $itemID, $response['body'], 'JSON');

				$recordType = $result['recordType'];
				
				//process item
				$result = processDspaceRecord($response['body']);
				
				$record = $result['record'];

				$message .= $result['report'];
				
				//save it in the database
				$result = saveValues('dspace', $itemID, $record, NULL);

				$result = saveValues('repository', $handle, $record, NULL);

				$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');

				//thesis and dissertation editing will redirect to the thesis submission form at the editMetadata step
				if($type == 'Thesis' || $type == 'Dissertation')
				{
					//continue to the main form
					header("Location: reviewCenter.php?formType=checkThesisSubmission&itemUUID=$itemID&action=editMetadata&handle=$handle");
					exit();
				}

				$identifierFields = array('dspace.handle','dc.identifier.doi','dc.identifier.arxivid','dc.identifier.eid','dc.identifier.wosut','dc.identifier.pmid','dc.identifier.pmcid','dc.identifier.ccdc','dc.identifier.github','dc.identifier.bioproject');
			
				foreach($identifierFields as $identifierField)
				{
					$id = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, $identifierField), array('value'), 'singleValue');
					
					if(!empty($id))
					{
						$identifiers[$id] = $identifierField;
					}
				}
			}
		}
		else
		{
			echo 'no IDs were entered';
		}

		if(!empty($identifiers))
		{
			foreach($identifiers as $id => $idField)
			{			
				$idInIRTS = getValues($irts, setSourceMetadataQuery('irts', NULL, NULL, $idField, $id), array('idInSource'), 'singleValue');
				
				$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, $idField, $id), array('idInSource'), 'singleValue');
				
				if(!empty($idInIRTS) && !empty($handle))
				{
					break;
				}
			}
		}

		if(empty($handle))
		{
			echo 'no existing repository item found.';
		}
		elseif(empty($idInIRTS))
		{
			$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');
			
			$doi = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.doi'), array('value'), 'singleValue');
		
			$idInIRTS = generateNewID('irts');
		
			$result = saveValue('irts', $idInIRTS, 'irts.source', 1, 'dspace', NULL);
			
			$parentRowID =  $result['rowID'];
				
			$result = saveValue('irts', $idInIRTS, 'irts.idInSource', 1, $itemID, $parentRowID);
			
			if(!empty($doi))
			{
				$result = saveValue('irts', $idInIRTS, 'dc.identifier.doi', 1, $doi, NULL);
			}

			$result = saveValue('irts', $idInIRTS, 'dc.type', 1, $type, NULL);

			$result = saveValue('irts', $idInIRTS, 'irts.status', 1, 'inProcess', NULL);
			
			$result = saveValue('irts', $idInIRTS, 'irts.harvest.basis', 1, 'Added manually', NULL);
		}
		
		if(!empty($idInIRTS))
		{
			$type = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, "dc.type"), array('value'), 'singleValue');
			
			// continue to the main form
			header("Location: reviewCenter.php?formType=editExisting&itemType=".str_replace(' ', '+', $type)."&idInIRTS=$idInIRTS");
			exit();
		}
		else
		{
			echo 'no existing idInIRTS.';
		}
	}
	else //display form
	{
		echo 'Edit existing record with a given ID.<br><hr>
			<form method="post" action="reviewCenter.php?formType=editExistingItem">
				<div>
				<div class="form-group">
				  <label for="doi">DOI:</label>
				  <textarea class="form-control" rows="1" name="doi"></textarea>
				</div>
				<div class="form-group">
				  <label for="arxivID">arXiv ID:</label>
				  <textarea class="form-control" rows="1" name="arxivID"></textarea>
				</div>
				<div class="form-group">
				  <label for="handle">Handle:</label>
				  <textarea class="form-control" rows="1" name="handle"></textarea>
				</div>';
				
				echo '</div><input class="btn btn-primary" type="submit" name="editItem" value="Load Existing Item Record for Reprocessing"></input>
			</form></div>';
	}
?>
