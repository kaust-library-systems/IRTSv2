<?php
//Define function to process a retrieved Pure JSON record for any API endpoint
function processPureRecord($baseField, $input)
{
	$output = [];
		
	foreach($input as $key => $value)
	{
		if(is_array($value)&&!empty($value))
		{
			if(is_numeric(array_keys($value)[0]))
			{
				foreach($value as $childKey => $childValue)
				{
					if(is_array($childValue)&&!empty($childValue))
					{						
						$field = $baseField.'.'.$key.'JSON';
				
						$valueJSON = json_encode($childValue);
						
						$output[$field][$childKey]['value'] = $valueJSON;
						
						$output[$field][$childKey]['children'] = processPureRecord($baseField, $childValue);
					}
				}
			}
			else
			{
				$field = $baseField.'.'.$key.'JSON';
			
				$valueJSON = json_encode($value);
				
				$output[$field][0]['value'] = $valueJSON;
				
				$output[$field][0]['children'] = processPureRecord($baseField, $value);
			}
		}
		elseif(is_string($value))
		{
			$field = $baseField.'.'.$key;
			
			$output[$field][]['value'] = $value;
		}
		elseif(is_bool($value))
		{
			if($value)
			{
				$value = 'TRUE';
			}
			else
			{
				$value = 'FALSE';
			}
			
			$field = $baseField.'.'.$key;
			
			$output[$field][]['value'] = $value;
		}
	}

	return $output;
}
