<?php
/*

**** This function generates a form to be displayed in the review center

** Parameters :
	$selections : the selections to be persisted in the form
	$record : the metadata record of the item
	$template : the template for this item type
	$step : the current step in the review process
	$page : the current page number
	$idInIRTS : the idInSource of the item in IRTS

** Returns : 
	$form : the form to be displayed in the review center

*/

//--------------------------------------------------------------------------------------------

function displayForm($selections, $record, $template, $step, $page, $idInIRTS)
{	
	$form = '';

	//start the form
	$form .= '<form method="post" action="reviewCenter.php?'.$selections.'">';

	//include the full record as a hidden input
	$form .= '<input type="hidden" id="recordJSON" name="recordJSON" value="'.htmlspecialchars(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT)).'">';

	//include empty input for recording deleted entries
	$form .= '<input type="hidden" id="removedEntries" name="removedEntries" value="">';

	while(!in_array(key($template['steps']), [$step, NULL]))
	{
		next($template['steps']);
	}

	//identify next step so that it is selected in the dropdown
	if(next($template['steps']))
	{
		$nextStep = key($template['steps']);
	}
	else
	{
		$nextStep = 'review';
	}

	//add entries (labels and values) to form
	$form .= addEntriesToForm($record, $template, $step, TRUE, []);

	if($step !== 'review')
	{
		$form .= '<input type="hidden" name="page" value="'.($page).'">
			<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">';
		
		if($step === 'acknowledgements' && in_array($record['dc.type'][0], array('Poster', 'Presentation')))
		{
			$form .= '<button class="btn btn-lg btn-warning" type="submit" name="action" value="jumpToReview">Jump to Review Step (no CC License, no file will be uploaded)</button>';
		}
		
		$form .= '<div class="form-group">
			<label for="step">Select step to jump to: </label></br>';
		
		$form .= '<select name="step">';
					
		foreach(array_keys($template['steps']) as $templateStep)
		{
			if($templateStep === $nextStep)
			{
				$form .= '<option selected="selected" value="'.$templateStep.'">'.$templateStep.'</option>';
			}
			else
			{
				$form .= '<option value="'.$templateStep.'">'.$templateStep.'</option>';
			}
		}
		
		if($nextStep === 'review')
		{
			$form .= '<option selected="selected" value="review">review</option>';
		}
		else
		{
			$form .= '<option value="review">review</option>';
		}

		$form .= '</select>';
		
		$form .= '</div>';
		
		$form .= '<button class="btn btn-lg btn-success" type="submit" name="action" value="deposit">Proceed to Next Step (Or Select Other Step Above)</button>';
			
		$form .= '</form>';
	}
	else
	{
		$form .= '<input type="hidden" name="page" value="'.($page).'">
			<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
			<button class="btn btn-lg btn-success" type="submit" name="action" value="save">Save Updated Metadata</button>
		</form>';
	}

	return $form;
}