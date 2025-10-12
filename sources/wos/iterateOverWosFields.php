<?php	
	//Define function to iterate over fields
	function iterateOverWosFields($source, $output, $fieldParts, $field, $place, $value)
	{
		//field names should be text, not numbers
		if(!is_numeric($field))
		{
			$fieldParts[] = $field;
		}
		else
		{
			$place = $field;
		}

		$currentField = mapField($source, $source.'.'.implode('.', $fieldParts), '');

		//$currentField = $source.'.'.implode('.', $fieldParts);
		
		if(is_array($value))
		{
			foreach($value as $field => $value)
			{
				$output = iterateOverWosFields($source, $output, $fieldParts, $field, $place, $value);
			}
		}
		elseif(!empty($value))
		{
			$output[$currentField][]['value'] = $value;
		}

		return $output;
	}
