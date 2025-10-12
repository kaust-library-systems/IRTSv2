<?php
	include 'snippets/forManuscriptReceipt/constructReplyFormButtonTemplate.php';
	
	include 'snippets/forManuscriptReceipt/constructReplies.php';
	
	$message .= '<hr>
	<div class="col-sm-12 border border-dark rounded">
	<details open="">
		<summary>Email Received</summary>
		<p>'.$receivedEmail.'</p>
		</details>							
	</div>';

	$message .= '
	<hr>
	<div class="col-sm-12 alert-warning border border-dark rounded">
	<b>Instructions:</b>
		<ol>
			<li>Open the received file(s):
			'.$receivedFilesList.'
			</li>
			<br>

			<li>Check that the file is actually for this item (title, authors, etc.).
			<br>
				<details>
				<summary>If not (or if there are any other problems/questions), </summary>'.str_replace(
					'{bodyFieldPlaceholder}', 
					'<b>Enter a note about the problem with this item:</b>
					<textarea class="form-control" rows="2" name="body"></textarea>', 
						str_replace(
							'{buttonType}', 
							'btn-warning', 
							str_replace(
								'{action}', 
								'sendNoteToRepository', 
								str_replace(
									'{label}', 
									'Send a note to the repository inbox for follow up', $replyFormButtonTemplate)))).'
				</details>
			</li>
			<br>

			<li>Check that the files are for the correct version.
				<br>
				<details>
				<summary>If the author sent the publisher PDF version, but we need the accepted manuscript version, </summary> '.str_replace(
					'{bodyFieldPlaceholder}', 
					'<b>Draft Message:</b>
					<textarea class="form-control" rows="15" name="body">'.$requestForOtherVersion.'</textarea>', 
						str_replace(
							'{buttonType}', 
							'btn-warning', 
							str_replace(
								'{action}', 
								'sendRequestForCorrectVersion', 
								str_replace(
									'{label}', 
									'Request the correct version', $replyFormButtonTemplate)))).'
				</details>
			</li>
			<br>';

	if(!empty($existingFilesList))
	{
		$message .= '<li>This item has existing file(s).
			<ol type="a">

				<li>Open the existing file(s):
				'.$existingFilesList.'
				</li>

				<li>If appropriate (for example, old file is a preprint), '.str_replace(
					'{bodyFieldPlaceholder}', 
					'<b>This is the email that will be sent after old file removal and new file transfer both complete successfully:</b>
					<textarea class="form-control" rows="12" name="body">'.$replyOnSuccess.'</textarea>', 
						str_replace(
							'{buttonType}', 
							'btn-success', 
							str_replace(
								'{action}', 
								'addReceivedFiles', 
								str_replace(
									'{label}', 
									'Replace the old file(s) with the new one(s) and send the response', $replyFormButtonTemplate)))).'</li>
			</ol>
		</li>';
	}
	else
	{
		$message .= '
		<li>If appropriate (file is for this item, version is correct, etc.), '.str_replace(
			'{bodyFieldPlaceholder}', 
			'<b>This is the email that will be sent after file transfer completes successfully:</b>
			<textarea class="form-control" rows="12" name="body">'.$replyOnSuccess.'</textarea>', 
				str_replace(
					'{buttonType}', 
					'btn-success', 
					str_replace(
						'{action}', 
						'addReceivedFiles', 
						str_replace(
							'{label}', 
							'Add the received file(s) and send the response', $replyFormButtonTemplate)))).'</li>';
	}
	
	$message .= '</ol>
	</div>
	<hr>';
?>