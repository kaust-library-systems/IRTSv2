<?php	
	//Define function to iterate over Lens.org metadata record fields
	function iterateOverLensFields($source, $record, $fieldParts, $field, $value)
	{
		if(!empty($value))
		{			
			if(!is_numeric($field))
			{
				$fieldParts[] = $field;
			}
			
			//for arrays we have to iterate further
			if(!is_array($value))
			{
				$currentField = $source.'.'.implode('.', $fieldParts);
				
				//map to standard field name
				$field = mapField($source, $currentField, '');

				$record[$field][]['value'] = (string)$value;
			}				
			else
			{
				foreach($value as $childField => $childValue)
				{
					$record = iterateOverLensFields($source, $record, $fieldParts, $childField, $childValue);
				}			
			}
		}
		return $record;
	}
