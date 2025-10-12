<?php
	/*

	**** This function recursively iterates over an array to save values as well as the values of child fields.

	** Parameters :
		$source : name of the source system.
		$idInSource : id of this record in the source system.
		$input : the record or subrecord as an array of field names and metadata values.
		$parentRowID : if the input is the subrecord of children of a value, this will be the parent row's rowID.
		$existingFieldsToIgnore : an array of field names to ignore when marking metadata as deleted.
		$completeRecord : a boolean to indicate whether the input is a complete record or a partial record.
		
	** Output : returns a report.

	*/
	//------------------------------------------------------------------------------------------------------------
	
	function saveValues($source, $idInSource, $input, $parentRowID, $existingFieldsToIgnore = '', $completeRecord = TRUE)
	{
		global $irts, $errors;
		
		$report = '';
		
		$saveValuesTime = microtime(true);

		foreach($input as $field=>$values)
		{
			//handle flat fields
			if(is_string($values))
			{
				$values = array(array('value' => $values));
			}
			
			foreach($values as $place => $value)
			{
				if(!empty($value['value']))
				{
					$result = saveValue($source, $idInSource, $field, $place, $value['value'], $parentRowID);
		
					$rowID = $result['rowID'];
					
					$report .= $source.' '.$idInSource.': '.$field.' '.$place.' child of '.$parentRowID.' - '.$result['status'].PHP_EOL;
					
					if(!empty($value['children']))
					{
						$report .= saveValues($source, $idInSource, $value['children'], $rowID);
					}
				}
			}

			if(isset($place))
			{
				//Mark existing entries with place greater than current count as deleted
				markExtraMetadataAsDeleted($source, $idInSource, $parentRowID, $field, $place, '');
			}
		}

		//If the input is a complete record, mark metadata fields previously but no longer used on the item as deleted
		if($completeRecord)
		{
			//List of metadata fields in the current record
			$currentFields = array_keys($input);

			if(!empty($existingFieldsToIgnore))
			{
				$currentFields = array_merge($currentFields, $existingFieldsToIgnore);
			}

			//Mark metadata fields previously but no longer used on the item as deleted
			markExtraMetadataAsDeleted($source, $idInSource, $parentRowID, '', '', $currentFields);
		}
		
		if(is_null($parentRowID))
		{
			$saveValuesTime = microtime(true)-$saveValuesTime;

			insert($irts, 'messages', array('process', 'type', 'message'), array('saveValuesTime', 'report', $source.' '.$idInSource.': '.$saveValuesTime.' seconds'));
		}

		return $report;
	}
