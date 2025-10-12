<?php	
	//Define function to process crossref results
	function processCrossrefRecord($sourceData)
	{
		global $irts, $newInProcess, $errors;
		
		$report = '';

		$source = 'crossref';
		
		$sourceDataAsJSON = json_encode($sourceData);
		
		$idInSource = $sourceData['DOI'];
		
		//Save copy of item JSON
		$result = saveSourceData($irts, $source, $idInSource, $sourceDataAsJSON, 'JSON');

		$recordType = $result['recordType'];
		
		//List of metadata fields used on the item with keys as field names and values as field place counts
		$originalFieldsPlaces = array();

		//Current field names will reflect the mappings to standard fields and will sometimes differ from the original field names
		$currentFields = array();
		
		foreach($sourceData as $field => $value)
		{
			//special check to set "Preprint" as the type for SSRN records
			if($field == 'type' && strpos($idInSource, '10.2139/ssrn.') !== FALSE)
			{
				$value = 'Preprint';
			}
			//special check to set "Presentation" as the type for EGU conference abstracts (some may be posters, but the processor will have to try to correct those manually)
			elseif($field == 'type' && strpos($idInSource, '10.5194/egusphere-egu') !== FALSE)
			{
				$value = 'Presentation';
			}
			
			$fieldParts = array();
			
			$parentRowID = NULL;
			
			iterateOverCrossrefFields($source, $idInSource, $originalFieldsPlaces, $currentFields, $field, $fieldParts, $value, $parentRowID);
		}		
		
		$currentFields = array_unique($currentFields);
		
		markExtraMetadataAsDeleted($source, $idInSource, NULL, '', '', $currentFields);

		//list of date fields to use as the publication date, in order of preference
		$dateFields = array('crossref.date.published-online', 'crossref.date.published-print', 'crossref.date.created');

		foreach($dateFields as $dateField)
		{
			//echo $dateField.' date searched'.PHP_EOL;
			
			$value = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $dateField), array('value'), 'singleValue');

			if(!empty($value))
			{
				//echo $dateField.' date found '.$value.PHP_EOL;
				
				//echo TODAY.PHP_EOL;
				
				if($value <= TODAY)
				{
					//echo 'Issue date found'.PHP_EOL;
					
					$result = saveValue($source, $idInSource, 'dc.date.issued', 1, $value, NULL);
					
					break 1;
				}
			}
		}
		
		return ['recordType' => $recordType, 'report' => $report];
	}
