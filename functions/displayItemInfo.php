<?php
/*

**** This function adds item details to the message to be displayed in the review center

** Parameters :
	$metadata : the item object from the repository
	$template : the template for this item type
	$step : the current step in the review process
	$showEmptyFields : whether to show empty fields
	$fieldsToIgnore : fields to ignore

** Returns : 
	$message : the message to be displayed in the review center

*/

//--------------------------------------------------------------------------------------------

function displayItemInfo($metadata, $template = '', $step = 'initial', $showEmptyFields = FALSE, $fieldsToIgnore = array())
{	
	$message = '';

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

	foreach($template['steps'][$step] as $field)
	{
		//do not show empty fields unless specified
		if(empty($metadata[$field]) && !$showEmptyFields)
		{
			continue;
		}

		//do not show ignored fields
		if(in_array($field, $fieldsToIgnore))
		{
			continue;
		}
		
		//set label if set in template, otherwise use the field name
		if(isset($template['fields'][$field]['label']))
		{
			$label = $template['fields'][$field]['label'];
		}
		else
		{
			$label = $field;
		}

		$message .= '<b>'.$label.':</b> ';

		if(isset($metadata[$field]))
		{
			if(is_array($metadata[$field]))
			{
				$values = array();
				foreach($metadata[$field] as $key => $value)
				{
					//child fields will have strings (field names) as keys
					if(is_int($key))
					{
						if(isset($value['value']))
						{
							$value = $value['value'];
						}
	
						// check if the author is from KAUST = green background 
						if($field == 'dc.contributor.author' && isset($metadata[$field]['dc.contributor.affiliation'][$key]))
						{
							if(institutionNameInString(implode('","', $metadata[$field]['dc.contributor.affiliation'][$key])))
							{
								$value = '<span style="background-color:#bcf5bc;">'.$value.'</span>';
							}
						}
	
						if(strpos($value, 'http')===0)
						{
							$values[] = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
						}
						elseif(!empty($template['fields'][$field]['baseURL']))
						{
							$values[] = '<a href="'.$template['fields'][$field]['baseURL'].$value.'" target="_blank">'.$value.'</a>';
						}
						else
						{
							$values[] = $value;
						}
					}
				}
				$value = implode('; ', $values);
			}
			else
			{
				$value = $metadata[$field];
			}
		}
		else
		{
			$value = '';
		}
		
		$message .= $value.'<br>';
	}

	return $message;
}