<?php
	// Snippet to display the process buttons for an item in the review center
	echo '<br><br><div class="col-lg-6"><form method="post" action="reviewCenter.php?'.$selections.'">
	<input type="hidden" name="page" value="'.($page).'">
	<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
	<input type="hidden" name="recordJSON" value="'.htmlspecialchars(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT)).'">
	<button class="btn btn-block btn-warning" type="submit" name="action" value="changeTypeAndTemplate">-- Wrong Type: Change Type and Template --</button>';

	// If the publication record has no DOI, display a button to go to form to add a DOI to the record
	if(!isset($record['dc.identifier.doi'][0]) && in_array($record['dc.type'][0], array('Article', 'Book', 'Book Chapter', 'Conference Paper', 'Poster', 'Presentation', 'Unknown')))
	{
		echo '
		<div class="col-sm-12 alert-warning border border-dark rounded">
			<b>The record does not have a DOI, please search for one and add it through the below button if you find one.</b>
			<br>
			<br>
			<button class="btn btn-block btn-info" type="submit" name="action" value="addItemDOIManually">-- Add: Item\'s DOI Manually --</button>
			<br>
		</div>';
	}
	
	// If no related publication handle has been found for a dataset record, display button to go to form to add a related publication
	if(!isset($record['dc.related.publicationHandle'][0]) && !isset($record['dc.related.publicationDOI'][0]) && in_array($record['dc.type'][0], HANDLING_RELATIONS))
	{
		echo '
		<div class="col-sm-12 alert-warning border border-dark rounded">
			<b>The dataset or software record does not have a related publication identified, please search for one and add the relationship through the below button if you find one.</b>
			<br>
			<br>
			<button class="btn btn-block btn-info" type="submit" name="action" value="addRelatedPublication">-- Add Related Publication --</button>
			<br>
		</div>';
	}

	if($formType === 'processNew')
	{
		echo '<input type="hidden" name="step" value="initial">
		<button class="btn btn-block btn-danger" type="submit" name="action" value="reject">-- Reject: Not a '.INSTITUTION_ABBREVIATION.'-affiliated or '.INSTITUTION_ABBREVIATION.'-funded Item --</button>
		<button class="btn btn-block btn-success" type="submit" name="action" value="deposit">-- Deposit: '.INSTITUTION_ABBREVIATION.' Affiliated or Funded Item --</button>
		<button class="btn btn-block btn-warning" type="submit" name="action" value="addNote">-- Problem Item: Add Note For Admin Review --</button>
		<button class="btn btn-block btn-primary" type="submit" name="action" value="skip">-- Skip: Take No Action and Move to Next Item --</button>';
	}
	elseif(in_array($formType, array('editExisting','review')))
	{		
		/* <form method="post" action="reviewCenter.php?'.$selections.'">
			<input type="hidden" name="transferType" value="createNewItem">
			<input type="hidden" name="page" value="'.($page).'">
			<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
			<button class="btn btn-block btn-warning" type="submit" name="action" value="transfer">-- Directly add as new item in DSpace : Skip metadata editing --</button>
		</form> */
		echo '<div class="form-group">
			<label for="step">Select step to jump to: </label></br>
			<select name="step">';
						
			foreach(array_keys($template['steps']) as $templateStep)
			{
				if(!in_array($templateStep, array('UnpaywallStep','dataRelations')))
				{
					echo '<option value="'.$templateStep.'">'.$templateStep.'</option>';
				}
			}
			
			echo '<option value="review">review</option>
			</select>
			</div>
			<button class="btn btn-block btn-success" type="submit" name="action" value="deposit">-- Reprocess Item : Starting with Selected Step --</button>
			<button class="btn btn-block btn-warning" type="submit" name="action" value="addNote">-- Problem Item: Add Note For Later Review --</button>';
		
		/* <button class="btn btn-block btn-danger" type="submit" name="action" value="reject">-- Ignore Permanently --</button>
			<button class="btn btn-block btn-primary" type="submit" name="action" value="skip">-- Skip: Take No Action and Move to Next Item --</button> */
		
		//If the formType is review and a problemType was selected, display the skip button so users can move through the list of records with that problem
		if($formType == 'review')
		{
			echo '<button class="btn btn-block btn-primary" type="submit" name="action" value="skip">-- Skip: Take No Action and Move to Next Item --</button>';
		}
	}

	echo '</form></div>';
?>
