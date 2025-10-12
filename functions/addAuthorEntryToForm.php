<?php
/*

**** This function adds an author entry to a form to be displayed in the review center

** Parameters :
	$metadata : the record metadata
	$template : the template for this item type
	$inputGroupID : the ID of the input group
	$field : the field to be added
	$key : the key of the field to be added
	$value : the value of the field to be added

** Returns : 
	$entry : the entry to be added to the form

*/

//--------------------------------------------------------------------------------------------

function addAuthorEntryToForm($metadata, $template, $inputGroupID, $field, $key, $value)
{	
	$entry = '';

	//add line to separate authors
	//$entry .= '<hr>';

	//show author in a box with a border
	$entry .= '<div class="input-group col-sm-12 border border-dark rounded" id="'.$inputGroupID.'">';

	//show author name
	$entry .= $value;

	//if author has ORCID, add icon as link
	if(!empty($metadata[$field]['dc.identifier.orcid'][$key][0]))
	{
		$entry .= '<a href="'.$template['fields']['dc.identifier.orcid']['baseURL'].$metadata[$field]['dc.identifier.orcid'][$key][0].'"><img id="orcid-id-icon" src="https://orcid.org/sites/default/files/images/orcid_24x24.png" width="24" height="24" alt="ORCID iD icon"/></a>';
	}

	/* // if the author is from KAUST, show with green background 
	if($field == 'dc.contributor.author' && isset($metadata[$field]['dc.contributor.affiliation'][$key]))
	{
		if(institutionNameInString(implode('","', $metadata[$field]['dc.contributor.affiliation'][$key])))
		{
			$entry .= '<span style="background-color:#bcf5bc;">'.$value.'</span>';
		}
	} */

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

			//set form group class
			$formGroupClass = 'form-group col-sm-12';

			//if child is empty, set form group class to not display
			if(empty($metadata[$field][$child][$key][0]))
			{
				$formGroupClass .= ' d-none';
			}

			//add affiliation class for dc.contributor.affiliation
			if($child === 'dc.contributor.affiliation')
			{
				$formGroupClass .= ' affiliation';
			}

			//start child form group div
			$entry .= '<div class="'.$formGroupClass.'"><b><label for="record['.$field.']['.$child.']['.$key.']">'.$label.':</label></b>';

			//if there is no child value, add an empty value
			if(empty($metadata[$field][$child][$key][0]))
			{
				$metadata[$field][$child][$key][0] = '';
			}

			//add hidden input for child
			foreach($metadata[$field][$child][$key] as $childKey => $value)
			{				
				$entry .= '<br><input type="hidden" name="record['.$field.']['.$child.']['.$key.'][]" value="'.$value.'" data-changed="false">';

				$entry .= '<span>'.$value.'</span>';

				$entry .= '</input>';
			}

			//close child form group div
			$entry .= '</div>';
		}
	}

	//add button to make author editable via javascript
	$entry .= '<button type="button" class="btn btn-primary edit-button" onclick="toggleAuthorEntry(\''.$inputGroupID.'\')">Edit Author</button>';

	//close author box
	$entry .= '</div>';

	return $entry;
}