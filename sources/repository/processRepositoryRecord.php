<?php	
	//Define function to process XOAI metadata for a single repository item
	function processRepositoryRecord($item, &$sourceReport = '')
	{
		global $irts;
		
		$source = 'repository';
		
		$recordType = '';
		
		$nameSpacesToIgnore = array("others", "repository", "license");
		
		$idInSource = str_replace(REPOSITORY_OAI_ID_PREFIX, '', $item->header[0]->identifier);
		//$idInSource = str_replace(REPOSITORY_OAI_ID_PREFIX, '', $item->identifier);
		$modified = (string)$item->header[0]->datestamp;
		//$modified = (string)$item->datestamp;
		$field = 'dspace.date.modified';
		$rowID = mapTransformSave($source, $idInSource, '', $field, '', 1, $modified, NULL);
		
		$sourceReport .= ' - Handle: '.$idInSource.' - Modified Timestamp: '.$modified.PHP_EOL;
		
		$check = $irts->query("SELECT rowID FROM sourceData WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND deleted IS NULL");
		
		if((string)$item->header[0]['status']==='deleted')
		//if((string)$item['status']==='deleted')
		{
			$sourceReport .= '- DELETED ITEM'.PHP_EOL;		
			
			//if matched in database			
			if(mysqli_num_rows($check) !== 0)
			{
				$sourceReport .= '- DELETED'.PHP_EOL;
				if(update($irts, 'sourceData', array("deleted"), array(date("Y-m-d H:i:s"), $idInSource), 'idInSource'))
				{
					$recordType = 'deleted';
					//also mark all related information in metadata table with deleted timestamp
					update($irts, 'metadata', array("deleted"), array(date("Y-m-d H:i:s"), $idInSource), 'idInSource');
				}
				else
				{
					$recordType = 'error';
					$error = end($errors);
					$sourceReport .= ' - '.$error['type'].' error: '.$error['message'].PHP_EOL;
				}
			}
			else
			{
				$recordType = 'deleted';
				$sourceReport .= '- Item Already Deleted'.PHP_EOL;
			}

			$saveSourceDataResult = array('recordType' => $recordType);
		}
		else
		{		
			//Save copy of item XML
			$sourceData = $item->asXML();
			
			//$saveSourceDataResult = saveSourceData($irts, $source, $idInSource, $sourceData, 'XML');
			$saveSourceDataResult = saveSourceData($irts, 'oai', $idInSource, $sourceData, 'XML');
				
			//Save collection and community handles as metadata
			foreach($item->header[0]->setSpec as $value)
			//foreach($item->setSpec as $value)
			{
				if(strpos($value, 'com')!==FALSE)
				{
					$communities[] = $value;
				}
				elseif(strpos($value, 'col')!==FALSE)
				{
					$collections[] = $value;
				}
			}
			
			//Save community handles
			$field = 'dspace.community.handle';
			$place = 0;
			foreach($communities as $value)
			{
				$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $value, NULL);
				$place++;
			}
			markExtraMetadataAsDeleted($source, $idInSource, NULL, $field, $place-1, '');
			
			//Save collection handles			
			$field = 'dspace.collection.handle';
			$place = 0;
			foreach($collections as $value)
			{
				$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $value, NULL);
				$place++;
			}
			markExtraMetadataAsDeleted($source, $idInSource, NULL, $field, $place-1, '');
			
			//save metadata and bitstream info
			foreach($item->metadata[0]->metadata[0]->element as $namespace)
			{
				if((string)$namespace[0]['name']==='bundles')
				{
					$bundlePlace = 0;
					foreach($namespace->element as $element)
					{
						$bundleName = (string)$element[0]->field;
						$field = 'dspace.bundle.name';
						$bundleNameRowID = mapTransformSave($source, $idInSource, '', $field, '', $bundlePlace, $bundleName, NULL);

						$bitstreamPlace = 0;
						foreach($element->element->element as $bitstream)
						{
							//Make list of metadata fields used on the bitstream
							$currentBitstreamFields = array();
							
							//First find bitstream URL which will has uuid which will serve as parent row of other bitstream metadata
							foreach($bitstream->field as $bitstreamField)
							{
								if((string)$bitstreamField[0]['name']==='url')
								{
									$field = 'dspace.bitstream.uuid';
									
									$value = (string)$bitstreamField;

									//value is in form https://repository.kaust.edu.sa/bitstreams/{uuid}/download
									$uuid = explode('/', $value)[4];
									$bitstreamUUIDRowID = mapTransformSave($source, $idInSource, '', $field, '', $bitstreamPlace, $uuid, $bundleNameRowID);											
								}
							}
							
							foreach($bitstream->field as $bitstreamField)
							{
								$childPlace = 0;
								$field = 'dspace.bitstream.'.(string)$bitstreamField[0]['name'];
								$value = (string)$bitstreamField;
								$rowID = mapTransformSave($source, $idInSource, '', $field, '', $childPlace, $value, $bitstreamUUIDRowID);
								$currentBitstreamFields[] = $field;
							}
							
							markExtraMetadataAsDeleted($source, $idInSource, $bitstreamUUIDRowID, '', '', $currentBitstreamFields);
							
							$bitstreamPlace++;									
						}
						markExtraMetadataAsDeleted($source, $idInSource, NULL, 'dspace.bitstream.uuid', $bitstreamPlace-1, '');
						$bundlePlace++;
					}
					markExtraMetadataAsDeleted($source, $idInSource, NULL, 'dspace.bundle.name', $bundlePlace-1, '');
				}
			}
		}
		return $saveSourceDataResult;
	}	
		