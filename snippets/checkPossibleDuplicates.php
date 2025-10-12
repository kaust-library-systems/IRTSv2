<?php
	//html to display
	$message = '<div class="container">';

	 //check for duplicates
	if(!isset($_POST['action'])) {
		if(!isset($page)) {
			$page = 0;
		}

		$message .= '<h2>Check Possible Duplicates</h2>';

		$message .= '
			<div class="row">	
			<div class="col-sm-12 alert-warning border border-dark rounded">
				<b>Instructions:</b>
					<ol>
						<li>If the records are identical or are close versions of each other, they should be merged.</li>
						<li>If one record has a file and the other does not, the record with a file should be marked as the main record. (If it is out-of-date, then you will need to update the metadata separately after merging).</li>
						<li>If both records have files (or neither has a file), the newest record should be marked as the main record.</li>
						<li>If the records are not close versions of the same item, they should be kept as separate records.</li>
						<li>If one has an English title, and the other title is in German, the record with the English title should be marked as the main record.</li>
						<li>If in doubt, skip to the next set of possible duplicates.</li>
					</ol>
				</div>
			</div>';

		//get possible duplicate pair
		$possibleDuplicatePair = getValues(
			$irts, 
			"SELECT `idInSource` FROM `metadata` 
				WHERE `source` LIKE 'irts' 
				AND `field` LIKE 'irts.duplicate.status' 
				AND `value` LIKE 'Possible Duplicates to Check' 
				AND `deleted` IS NULL 
				ORDER BY `idInSource` 
				LIMIT $page, 1", 
			array('idInSource'), 
			'singleValue');

		if(empty($possibleDuplicatePair)) {
			$message .= 'No possible duplicate pairs found.';
		}
		else {
			
			$duplicateType = getValues(
				$irts, 
				"SELECT `value` FROM `metadata` 
					WHERE `source` LIKE 'irts' 
					AND `idInSource` LIKE '$possibleDuplicatePair'
					AND `field` LIKE 'irts.duplicate.type' 
					AND `deleted` IS NULL", 
				array('value'), 
				'singleValue'
			);

			$message .= '<h3>Duplicate Type: '.$duplicateType.'</h3>';
			
			$possibleDuplicateHandles = explode(':', $possibleDuplicatePair);

			$form = '<form method="post" action="reviewCenter.php?formType=checkPossibleDuplicates">
				<input type="hidden" name="possibleDuplicatePair" value="'.$possibleDuplicatePair.'">
				<input type="hidden" name="selections" value="'.$selections.'">
				<input type="hidden" name="page" value="'.$page.'">';

			//add section for each handle
			foreach($possibleDuplicateHandles as $place => $handle) {

				//create section with border
				$section = '<div class="row"><div class="col-md-12 border border-primary rounded">';

				$section .= '<p><b>Record '.($place+1).':</b> '.$handle.'</p>';

				//get itemID
				$itemID = getValues(
					$irts, 
					"SELECT value FROM metadata 
						WHERE source = 'repository' 
						AND idInSource = '$handle' 
						AND field = 'dspace.uuid' 
						AND deleted IS NULL", 
					array('value'), 
					'singleValue');

				//add links to repository record
				$section .= displayRepositoryLinks($itemID, $handle);

				//get type
				$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');

				//get template
				$template = prepareTemplate($type, 'initial');

				//get record
				$record = getRecord('repository', $handle, $template);

				//add item info
				$section .= displayItemInfo($record, $template, 'initial', FALSE, []);

				$section .= '<br><input type="radio" id="'.$handle.'" name="mainRecordHandle" value="'.$handle.'">
					<label for="'.$handle.'">Mark as main record (Main record will be kept. The other record will be made undiscoverable and will display a link to the main record).</label></p>';

				$section .= '</div></div>';

				$form .= $section;
			}

			//reason for merger field as dropdown list
			$form .= '<div class="row"><div class="col-md-12 border border-primary rounded">
				<div class="form-group">
				<label for="reasonForMerger">Reason for Merger:</label>
				<select class="form-control" name="reasonForMerger">
					<option value="">Select a Reason</option>
					<option value="duplicate">Records are the Same</option>
					<option value="version">Duplicate Record is an Old Version of the Main Record</option>
				</select>
			</div>';

			$form .= '
				<button class="btn btn-block btn-success" type="submit" name="action" value="merge">-- Merge Records --</button>
				<button class="btn btn-block btn-warning" type="submit" name="action" value="keepBoth">-- Keep Both Records --</button>
				<button class="btn btn-block btn-primary" type="submit" name="action" value="skip">-- Skip: Take No Action and Move to Next Item --</button>
				</form>
			</div></div>';

			$message .= $form;
		}
	}
	else {		
		$possibleDuplicatePair = $_POST['possibleDuplicatePair'];

		if($_POST['action'] == 'merge') {
			//merge records
			$mainRecordHandle = $_POST['mainRecordHandle'];

			if($mainRecordHandle == '') {
				$message .= 'No main record selected. Please select a main record to merge duplicates.';
			}
			elseif($_POST['reasonForMerger'] == '') {
				$message .= 'No reason for merger selected. Please select a reason for merging the records.';
			}
			else {
				$possibleDuplicateHandles = explode(':', $possibleDuplicatePair);
				
				foreach($possibleDuplicateHandles as $possibleDuplicateHandle) {
					if($possibleDuplicateHandle != $mainRecordHandle) {	
						$duplicateHandle = $possibleDuplicateHandle;
					}
				}

				$reasonForMerger = $_POST['reasonForMerger'];

				//get UUIDs for the main and duplicate records
				$mainUUID = getValues($irts, setSourceMetadataQuery('repository', $mainRecordHandle, NULL, 'dspace.uuid'), array('value'), 'singleValue');
				$duplicateUUID = getValues($irts, setSourceMetadataQuery('repository', $duplicateHandle, NULL, 'dspace.uuid'), array('value'), 'singleValue');

				//if the reason for merging is that the records are the same
				if($reasonForMerger == 'duplicate')
				{
					$provenanceStatement = 'Merged with '.$mainRecordHandle.' at '.date('Y-m-d H:i:s').' by '.$_SESSION['displayname'].' using the '.IR_EMAIL.' user account.';
					
					$result = mergeDuplicates($mainRecordHandle, $duplicateHandle, $mainUUID, $duplicateUUID, $provenanceStatement);
				}
				elseif($reasonForMerger == 'version') //if the reason for merging is that the duplicate record is an old version of the main record
				{
					$result = mergeVersions($mainRecordHandle, $duplicateHandle, $mainUUID, $duplicateUUID);
				}

				$status = $result['status'];

				if($status == 'success')
				{
					if($reasonForMerger == 'duplicate')
					{
						$pairStatus = 'Merged as Duplicates';
					}
					elseif($reasonForMerger == 'version')
					{
						$pairStatus = 'Merged as Versions';
					}
					
					//update status for this pair
					$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.status', 0, $pairStatus, NULL);
					
					echo '<div class="alert alert-success">Records merged successfully.</div>';

					//add note about files if version merger
					if($reasonForMerger == 'version')
					{
						echo '<div class="alert alert-warning">Handling Files: If the duplicate record has files and is an old version of the main record and the main record has no files, move the files from the duplicate record to the main record by downloading the files from the duplicate record and then uploading them to the main record through the <a href="reviewCenter.php?formType=uploadFile">Upload a File</a> form.</div>';
					}
				}
				else
				{
					$message = $result['message'];
					
					echo '<div class="alert alert-danger">Error merging records: 
						<details>
							<summary>Details</summary>
							<p> - '.$message.'</p>
						</details>
					</div>';
				}
			}
		}
		elseif($_POST['action'] == 'keepBoth') {
			//keep both records by setting new status for the pair
			$result = saveValue('irts', $possibleDuplicatePair, 'irts.duplicate.status', 0, 'Accept Both As Distinct Records', NULL);

			$message .= '<div class="alert alert-success">Both records kept as distinct records. No change in DSpace.</div>';
		}

		//button to go to the next item
		$message .= '<form method="post" action="reviewCenter.php?'.$selections.'">
			<input class="btn btn-primary" type="submit" name="checkPossibleDuplicates" value="Check Next Pair of Possible Duplicates"></input>
		</form>';
	}

	$message .= '</div>';

	echo $message;
