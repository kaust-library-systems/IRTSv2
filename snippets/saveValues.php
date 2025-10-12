<?php

$source = 'irts';

//compress relation fields before saving to the database
foreach($record as $field=>$values)
{
	if($field === 'dc.relationType')
	{
		foreach($values as $key => $relationType)
		{
			if(is_int($key)&&!empty($relationType))
			{
				foreach($record['dc.relationType']['dc.relatedIdentifier'][$key] as $relatedIdentifier)
				{
					if(!empty($value))
					{
						$record['dc.relation.'.$relationType][] = $relatedIdentifier;
					}
				}
			}
		}
	}
}
unset($record['dc.relationType']);

$recordToSave = array();

foreach($record as $field=>$values)
{
	if(!empty($values))
	{
		foreach($values as $key => $value)
		{
			//subfields will have strings (field names) as keys
			if(is_int($key)&&!empty($value))
			{
				$recordToSave[$field][$key]['value'] = $value;

				if(!empty($template['fields'][$field]['field']))
				{
					foreach($template['fields'][$field]['field'] as $child)
					{
						if(isset($record[$field][$child][$key]))
						{
							if(is_array($record[$field][$child][$key]))
							{
								foreach($record[$field][$child][$key] as $childKey => $value)
								{
									if(!empty($value))
									{
										$recordToSave[$field][$key]['children'][$child][$childKey]['value'] = $value;
									}
								}
							}
							else
							{
								//print_r($record[$field][$child][$key]);
							}
						}
					}
				}
			}
		}
	}
}

//These fields should not be marked as deleted, even though they are not in the record being saved.
$existingFieldsToIgnore = [
	'irts.source',
	'irts.idInSource',
	'irts.harvest.basis'
];

$result = saveValues('irts', $idInIRTS, $recordToSave, NULL, $existingFieldsToIgnore, TRUE);
