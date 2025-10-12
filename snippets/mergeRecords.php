<?php
	echo '<div class="container">';
	if(isset($_POST['mergeRecords']))
	{
		//handle form submission

		//get values from form
		$mainRecordHandle = trim($_POST['mainRecordHandle']);
		$duplicateHandle = trim($_POST['duplicateHandle']);

		//check that values are valid handles
		if(!preg_match('/^[\d]+\/[\d]+$/', $mainRecordHandle) || !preg_match('/^[\d]+\/[\d]+$/', $duplicateHandle))
		{
			echo '<div class="alert alert-danger">Invalid handle(s) entered. Please enter valid handles.</div>';
			return;
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
	else //display form
	{
		//instructions
		echo '<div class="alert alert-info">To merge records, enter the handle of the main record and the handle of the duplicate record. Select a reason for the merger from the dropdown list. If the records are the same, select "Records are the Same". If the duplicate record is an old version of the main record, select "Duplicate Record is an Old Version of the Main Record".</div>';

		//explain how different selections are handled differently
		echo '<div class="alert alert-warning">If you select "Records are the Same", the duplicate record will be made undiscoverable and its page updated to only display a link pointing to the main record. If you select "Duplicate Record is an Old Version of the Main Record", the duplicate record will be made undiscoverable and updated with a link to the main record as a new version. The main record will also be updated with a link to the duplicate record as a previous version.</div>';

		//explain how files will be handled
		echo '<div class="alert alert-danger">Handling Files: If the records are the same, no files need to be moved or copied (if one record has files and the other does not, please make the record with files the main record). If the duplicate record has files and is an old version of the main record and the main record has no files, move the files from the duplicate record to the main record by downloading the files from the duplicate record and then uploading them to the main record through the <a href="reviewCenter.php?formType=uploadFile">Upload a File</a> form.</div>';
		
		//main record handle field
		echo '<form method="post" action="reviewCenter.php?formType=mergeRecords">
			<div class="form-group">
			  <label for="mainRecordHandle">Main Record Handle:</label>
			  <textarea class="form-control" rows="1" name="mainRecordHandle"></textarea>
			</div>';

		//duplicate handle field
		echo '<div class="form-group">
			  <label for="duplicateHandle">Duplicate Record Handle:</label>
			  <textarea class="form-control" rows="1" name="duplicateHandle"></textarea>
			</div>';

		//reason for merger field as dropdown list
		echo '<div class="form-group">
			  <label for="reasonForMerger">Reason for Merger:</label>
			  <select class="form-control" name="reasonForMerger">
			  	<option value="">Select a Reason</option>
				<option value="duplicate">Records are the Same</option>
				<option value="version">Duplicate Record is an Old Version of the Main Record</option>
			  </select>
			</div>';
		
		//submit button
		echo '<input class="btn btn-primary" type="submit" name="mergeRecords" value="Merge Records"></input>
		</form>';
	}
	echo '</div>';
?>