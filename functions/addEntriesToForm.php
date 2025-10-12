<?php
/*

**** This function adds input fields to a form to be displayed in the review center

** Parameters :
	$metadata : the item object from the repository
	$template : the template for this item type
	$step : the current step in the review process
	$showEmptyFields : whether to show empty fields
	$fieldsToIgnore : fields to ignore

** Returns : 
	$entries : the entries to be added to the form

*/

//--------------------------------------------------------------------------------------------

function addEntriesToForm($metadata, $template = '', $step = 'initial', $showEmptyFields = FALSE, $fieldsToIgnore = array())
{	
	$entries = '';

	if(empty($template))
	{
		if(isset($metadata['dc.type'][0]['value']))
		{
			$type = $metadata['dc.type'][0]['value'];
		}
		else
		{
			$type = $metadata['dc.type'][0];
		}
		
		$template = prepareTemplate($type, $step);
	}

	//for review step, show all fields
	if($step === 'review')
	{
		$fields = array_keys($template['fields']);
	}
	else
	{
		$fields = $template['steps'][$step];
	}

	foreach($fields as $field)
	{
		//do not show empty fields unless specified
		if(!isset($metadata[$field]) && !$showEmptyFields)
		{
			continue;
		}

		//do not include ignored fields
		if(in_array($field, $fieldsToIgnore))
		{
			continue;
		}
		
		//skip display of child fields for the review step
		if($step === 'review' && in_array($field, $template['childFields']))
		{
			continue;
		}

		if(empty($template['fields'][$field]['note']))
		{
			$label = $template['fields'][$field]['label'];
		}
		else
		{
			$label = $template['fields'][$field]['label'].' ('.$template['fields'][$field]['note'].')';
		}

		//start form group div
		$entries .= '<div class="form-group"><b><label for="record['.$field.']">'.$label.':</label></b>';

		if(!empty($metadata[$field]))
		{
			$values = $metadata[$field];
		}
		else
		{
			$values = array(0=>array('value'=>''));
		}

		foreach($values as $key => $value)
		{
			//if key is not an integer, skip it, it is a child field
			if(is_int($key))
			{
				$inputGroupID = $field.'_'.$key;

				if(isset($value['value']))
				{
					$value = $value['value'];
				}
	
				// in the authors and review steps, first display the author entries with a toggle button to make them editable
				if($field == 'dc.contributor.author' && in_array($step, array('authors', 'review')))
				{
					$entries .= addAuthorEntryToForm($metadata, $template, $inputGroupID, $field, $key, $value);
				}
				else
				{
					//start input group div
					$entries .= '<div class="input-group col-sm-12" id="'.$inputGroupID.'">';
					
					//Default to textarea input
					if(empty($template['fields'][$field]['inputType']))
					{
						//see how large to make the textarea
						$textareaRows = (int)round(strlen($value)/100);
		
						if($textareaRows===0)
						{
							$textareaRows = 1;
						}
						
						// if the title or the description contain a dollar sign, pop up message
						if($field == 'dc.title' || $field == 'dc.description.abstract')
						{
							if( substr_count($value, '$') > 1 )
							{
								$entries .= '<div class="col-sm-12 alert-warning border border-dark rounded"><b> -- Important notes : <br></b>* This text contains dollar signs; Please fix it if needed as following : <br>
									• Case 1 : Use of $ to refer to dollars, if there are two $, the text in between will not be rendered normally and they need to be replaced with $\$$ . For example: 5$ and 6$ => 5$\$$ and 6$\$$<br>
									• Case 2 : Use of two $ as the delimiter, in our implementation this will make the formula display on a new line, should be replaced by $. For example: $N_t$ <br>
		
									</div>';
							}
						}
		
						$entries .= '<textarea class="form-control" rows="'.$textareaRows.'" name="recordChanges['.$field.']['.$key.']" data-changed="false">'.htmlentities($value).'</textarea>';

						$entries .= '<button id="remove_'.$inputGroupID.'" class="input-group-append btn btn-danger remove-me" >-</button><button id="add_'.$inputGroupID.'" class="input-group-append btn btn-success add-more" type="button">+</button>';
					}
					elseif($template['fields'][$field]['inputType']==='dropdown')
					{
						$entries .= '<select name="recordChanges['.$field.']['.$key.']">';
						
						$listValues = explode(',',$template['fields'][$field]['values']);
		
						foreach($listValues as $listValue)
						{
							if($listValue === $value)
							{
								$entries .= '<option selected="selected" value="'.$listValue.'">'.$listValue.'</option>';
							}
							else
							{
								$entries .= '<option value="'.$listValue.'">'.$listValue.'</option>';
							}
						}
		
						$entries .= '</select>';

						//display child fields if they exist
						if(!empty($template['fields'][$field]['field']))
						{
							foreach($template['fields'][$field]['field'] as $child)
							{
								if(empty($template['fields'][$child]['note']))
								{
									$label = $template['fields'][$child]['label'];
								}
								else
								{
									$label = $template['fields'][$child]['label'].' ('.$template['fields'][$child]['note'].')';
								}

								//add text input for child
								foreach($metadata[$field][$child][$key] as $childKey => $value)
								{				
									$entries .= '<textarea class="form-control" rows="1" name="recordChanges['.$field.']['.$child.']['.$key.'][]" data-changed="false">'.htmlentities($value).'</textarea>';
								}
							}
						}
					}
					elseif($template['fields'][$field]['inputType']==='radiobutton' ) //used for selecting among multiple OA versions returned from Unpaywall in the rights step
					{
						if( !empty($values) && !empty($values[0]))
						{
							$entries .= ' <br><input type="radio" name="recordChanges['.$field.'][]" value="'.$value.'">  &nbsp; <b>URL:</b> &nbsp; <a href="'.$value.'">'.$value.'</a>,  &nbsp; ' ;
		
							if(isset($record[$field][$template['fields'][$field]['field'][0]]))
							{	
								$entries .= '<b>Version:</b>  &nbsp;'.$record[$field][$template['fields'][$field]['field'][0]][$key];
							}
							$entries .= '</input><br>';
						}
						elseif( $valueCount == 0) 
						{
							$entries .= '<b>No Unpaywall results returned</b>';
						}
					}
		
					//end input group div
					$entries .= '</div>';
				}
			}
		}

		//end form group div
		$entries .= '</div>';
	}

	return $entries;
}