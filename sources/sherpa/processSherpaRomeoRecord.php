<?php
	//The Sherpa Romeo API v2 documentation is at: https://v2.sherpa.ac.uk/romeo/api.html
	function processSherpaRomeoRecord($sourceData)
	{
		global $irts, $newInProcess, $errors, $sourceReport;

		$source = 'sherpaRomeo';

		//We just want to save the most basic publisher record for now so that we can attach alternate names and set statements to it locally
		unset($sourceData['policies']);
		
		$crossrefPublisherName = '';
		
		if(isset($sourceData['publications']))
		{
			$publications = $sourceData['publications'];
			
			foreach($publications as $publication)
			{
				if(isset($publication['issns']))
				{
					foreach($publication['issns'] as $issn)
					{
						$crossrefDOI = getValues($irts, setSourceMetadataQuery('crossref', NULL, NULL, 'dc.identifier.issn', $issn['issn']), array('idInSource'), 'singleValue');
						
						if(!empty($crossrefDOI))
						{
							$crossrefPublisherName = getValues($irts, setSourceMetadataQuery('crossref', $crossrefDOI, NULL, 'dc.publisher'), array('value'), 'singleValue');
							
							if(!empty($crossrefPublisherName))
							{
								break 2;
							}
						}
					}
				}
			}
		}
		
		if(empty($crossrefPublisherName))
		{
			$recordType = 'skipped';
		}
		else
		{
			unset($sourceData['publications']);

			$sourceDataAsJSON = json_encode($sourceData);

			$idInSource = 'publisher_'.$sourceData['id'];

			//Save copy of item JSON
			$recordType = saveSourceData($irts, $sourceReport, $source, $idInSource, $sourceDataAsJSON, 'JSON');
			
			$place = 1;
			$field = 'crossref.publisher.name';
			$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $crossrefPublisherName, NULL);
			
			$setStatement = getValues($irts, "SELECT * FROM repositoryAuthorityControl.`setStatements` WHERE `Publisher` LIKE '$crossrefPublisherName'", array('setStatement'), 'singleValue');
			
			if(!empty($setStatement))
			{
				$field = 'irts.publisher.setStatement';
				$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $setStatement, NULL);
			}

			foreach($sourceData as $field => $values)
			{
				if(!is_array($values))
				{
					$field = "$source.publisher.$field";
					$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, (string)$values, NULL);
				}
				else
				{
					//print_r($values);
					if($field === 'name')
					{
						if(!empty($values[0]['name']))
						{
							$field = "$source.publisher.$field";
							$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, (string)$values[0]['name'], NULL);
						}
					}
					else
					{
						foreach($values as $key=>$value)
						{
							$subfield = "$source.publisher.$field.$key";
							
							if(is_array($value))
							{
								$value = print_r($value, TRUE);
							}
							
							$rowID = mapTransformSave($source, $idInSource, '', $subfield, '', $place, $value, NULL);
						}
					}
				}
			}
		}

		return $recordType;
	}
