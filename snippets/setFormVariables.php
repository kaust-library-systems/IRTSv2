<?php
	//store get variables in the session

	//selection keys
	$selectionKeys = array(
		'duplicateType', 
		'formType', 
		'itemType', 
		'problemType', 
		'sort', 
		'ignoreVariantTitles', 
		'harvestBasis', 
		'year');

	//all variables are unset after one item is processed, but selections are kept
	foreach ($_GET as $key => $value) 
	{
		if(in_array($key, $selectionKeys))
		{
			$_SESSION['selections'][$key] = $value;
		}		
		else
		{
			$_SESSION['variables'][$key] = $value;
		}
	}
	
	// recordJSON is the complete record in JSON format
	if(isset($_POST['recordJSON']))
	{
		$record = json_decode(htmlspecialchars_decode($_POST['recordJSON']), TRUE);

		unset($_POST['recordJSON']);

		//remove other possible record variables
		unset($_POST['record']);
	}

	//if an entry is removed, the entire field will be marked as changed
	$changedFields = [];

	//removedEntries is a list of entries that were removed from the record in the form
	if(isset($_POST['removedEntries']) && $_POST['removedEntries'] !== '')
	{
		$removedEntries = explode(',', $_POST['removedEntries']);

		//remove entries from the record
		foreach($removedEntries as $entry)
		{
			$entryParts = explode('_', $entry);
			$entryField = $entryParts[0];
			$entryIndex = (int)$entryParts[1];

			unset($record[$entryField][$entryIndex]);

			//mark the field as changed
			if(!in_array($entryField, $changedFields))
			{
				$changedFields[] = $entryField;
			}
		}

		unset($_POST['removedEntries']);
		unset($removedEntries);
		unset($entry);
	}

	//compare recordChanges to the record to see if the record has been changed, update record to reflect changes
	if(isset($_POST['recordChanges']))
	{
		$recordChanges = $_POST['recordChanges'];

		foreach($recordChanges as $field => $values)
		{
			foreach($values as $key => $value)
			{
				//if the key is a number, it is for the value
				if(is_numeric($key))
				{
					$key = (int)$key;
					
					$record[$field][$key] = $value;
				}
				else
				{
					//if the key is not a number, it is for a child field
					foreach($value as $childKey => $childValues)
					{
						$record[$field][$key][$childKey] = $childValues;
					}
				}
			}

			$lastKey = $key;

			//for changed fields, remove any excess entries
			if(in_array($field, $changedFields) && is_numeric($lastKey))
			{
				//loop through and check if the keys are numeric and are larger than the last key
				foreach($record[$field] as $key => $value)
				{
					if(is_numeric($key) && $key > $lastKey)
					{
						unset($record[$field][$key]);
					}
				}
			}
		}

		unset($_POST['recordChanges']);
		unset($recordChanges);
	}

	if(isset($record))
	{
		$_SESSION['variables']['record'] = $record;
	}

	//store posted variables in the session
	foreach ($_POST as $key => $value) 
	{			
		$_SESSION['variables'][$key] = $value;
	}
	
	//add all session selections to the current symbol table
	if(isset($_SESSION['selections']))
	{
		extract($_SESSION['selections']);
	}
	
	//add all session variables to the current symbol table
	if(isset($_SESSION['variables']))
	{
		extract($_SESSION['variables']);
	}
	
	if(!isset($page))
	{
		$page = 0;
	}
	
	//Default sort is "newestFirst"
	if(!isset($sort))
	{
		$sort = 'newestFirst';
	}
	
	//Translate sorting instructions for SQL query
	if($sort === 'newestFirst')
	{
		$sort = 'DESC';
	}
	elseif($sort === 'oldestFirst')
	{
		$sort = 'ASC';
	}	
?>