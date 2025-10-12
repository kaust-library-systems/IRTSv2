<?php
	//Define function to transform an array of fields and values into a new JSON item for DSpace
	function dspacePrepareItem($record, $uuid = NULL, $metadata = [])
	{
		if(!is_null($uuid))
		{
			$item['uuid'] = $uuid;
		}

		if(isset($record['dc.title'][0]['value'])){
			$item['name'] = $record['dc.title'][0]['value'];
		}
		else{
			$item['name'] = $record['dc.title'][0];
		}

		$item['inArchive'] = true;

		$item['discoverable'] = true;

		$item['withdrawn'] = false;

		$item['type'] = 'item';
		
		foreach($record as $field => $value)
		{
			if(!empty($value))
			{
				if(is_string($value))
				{
					$value = preg_replace('/\x{2010}/u','-', $value);
					
					$value = preg_replace('/\x{2009}/u',' ', trim($value));
					
					//$value = preg_replace('/[\n]+/','\\n', trim($value));
					
					$metadata[$field][] = array('value'=>$value);
				}
				elseif(is_array($value))
				{
					foreach($value as $value)
					{
						if(is_array($value))
						{
							if(isset($value['value'])){
								$value = $value['value'];
							}
							else{
								echo $field.' value does not have a value key:';
								
								print_r($value);
							}
						}
						
						$value = preg_replace('/\x{2010}/u','-', $value);
						
						$value = preg_replace('/\x{2009}/u',' ', trim($value));
						
						//$value = preg_replace('/[\n]+/','\\n', trim($value));
						
						if(!empty($value))
						{
							$metadata[$field][] = array('value'=>$value);
						}
					}
				}
			}	  		  
		}

		$item['metadata'] = $metadata;
		
		return json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
	}
