<?php
	include 'snippets/forEmbargoExtension/constructReplyFormButtonTemplate.php';
	
	include 'snippets/forEmbargoExtension/constructReplies.php';
	
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
			<li>Check that the embargo extension request is from the author (or their advisor), and is for this item.
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

			<li>If appropriate, '.str_replace(
			'{bodyFieldPlaceholder}', 
			'<ol type="a">
			<li><label for="selectedExtensionPeriod">Select the time period of extension (default to 1 year if no specific period is requested):</label>
			<select name="selectedExtensionPeriod" id="selectedExtensionPeriod">
			<option value="+6 months" >+6 months</option>
			<option value="+1 year" >+1 year</option>
			<option value="+2 years" >+2 years</option>
			<option value="+3 years" >+3 years</option>
			<option value="+5 years" >+5 years</option>
			</select>
			</li>
			OR
			<br>
			<li><label for="newEmbargoEndDate">Enter a specific embargo end date (YYYY-MM-DD):</label>
			<input type="text" id="newEmbargoEndDate" name="newEmbargoEndDate" placeholder="YYYY-MM-DD">
			</li>
			</ol>
			<br>
			<b>This is the email that will be sent after the embargo extension completes successfully:</b>
			<textarea class="form-control" rows="12" name="body">'.$replyOnSuccess.'</textarea>', 
				str_replace(
					'{buttonType}', 
					'btn-success', 
					str_replace(
						'{action}', 
						'approveExtension', 
						str_replace(
							'{label}', 
							'Extend the embargo and send the confirmation email', $replyFormButtonTemplate)))).'</li>';
	
	$message .= '</ol>
	</div>
	<hr>';
?>