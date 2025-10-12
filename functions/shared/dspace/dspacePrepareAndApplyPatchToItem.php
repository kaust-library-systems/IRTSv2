<?php
	//This function makes all requests needed to prepare and apply a patch to an item in DSpace
    function dspacePrepareAndApplyPatchToItem($handle, $newMetadata, $reviewerOrFunctionName, $item = NULL)
	{
        //make irts database connection available
        global $irts;
        
        //flag if new metadata is different from existing metadata
        $changed = FALSE;
        
        $report = '';

        $errors = [];
        
        $record = '';
        
        //if old metadata was not provided
        if(is_null($item))
        {
            //get existing metadata and itemID
            $response = dspaceGetItemByHandle($handle);

            if($response['status'] == 'success')
            {
                $patches = [];
                
                $item = json_decode($response['body'], TRUE);
            }
            else
            {
                $status = 'skipped';

                $report .= '-- item skipped - failed to get existing metadata for '.$handle.PHP_EOL.' -- REST API error response = '.print_r($response, TRUE).PHP_EOL;

                $errors[] = $report;
            }
        }
        
        //if $item is no longer NULL, then process it
        if(!is_null($item))
        {
        	$itemID = $item['uuid'];

          	//metadata as simple array
          	$record = dSpaceMetadataToArray($item['metadata']);
          
	        //loop through the new metadata and compare it to the old metadata to identify changes
	        foreach($newMetadata as $field => $values)
	        {
	            foreach($values as $key => $value)
	            {
	                $value = trim($value);
	
	                //if no old value is set and the new value is not empty, use add
	                if(!isset($record[$field][$key]) && !empty($value))
	                {
	                    $record[$field][$key] = $value;
	
	                    $patches[] = array("op" => "add",
	                        "path" => "/metadata/$field/-",
	                        "value" => array("value" => $value));
	
	                    $report .= '-- New '.$field.' "'.$value.'" at place '.$key.PHP_EOL;
	
	                    $changed = TRUE;
	                }
	                //if the field is set, but the value is different, use replace
	                elseif(isset($record[$field][$key]) && $record[$field][$key] !== $value)
	                {
	                    $oldValue = $record[$field][$key];
	
	                    //if new value is empty, use remove
	                    if(empty($value))
	                    {
	                        $patches[] = array("op" => "remove",
	                            "path" => "/metadata/$field/$key");
	
	                        unset($record[$field][$key]);
	
	                        $report .= '-- Removed '.$field.' old value "'.$oldValue.'" at place '.$key.PHP_EOL;
	                    }
	                    else
	                    {
	                        //if the new value is not empty, use replace
	                        $patches[] = array("op" => "replace",
	                            "path" => "/metadata/$field/$key",
	                            "value" => array("value" => $value));
	
	                        $record[$field][$key] = $value;
	
	                        $report .= '-- Replaced '.$field.' old value "'.$oldValue.'" with new value "'.$value.'" at place '.$key.PHP_EOL;
	                    }
	
	                    $changed = TRUE;
	                }
	
	                $lastNewKey = $key;
	            }
	
	            //check for excess values in the existing metadata and remove them
	            if(isset($record[$field]))
	            {
	                $keysToRemove = [];
	                
	                foreach($record[$field] as $key => $value)
	                {                        
	                    //if the key is greater than the last new key, it is excess and should be removed
	                    if($key > $lastNewKey)
	                    {
	                        $keysToRemove[] = $key;
	                    }
	                }
	
	                //reverse sort the keys so that we can remove the largest one first (otherwise the keys will shift)
	                rsort($keysToRemove);
	
	                foreach($keysToRemove as $keyToRemove)
	                {
	                    $patches[] = array("op" => "remove",
	                        "path" => "/metadata/$field/$keyToRemove");
	
	                    unset($record[$field][$keyToRemove]);
	
	                    $report .= '-- Removed '.$field.' at place '.$keyToRemove.PHP_EOL;
	
	                    $changed = TRUE;
	                }
	            }
	        }
	      }

        if($changed)
        {
            $patches[] = array("op" => "add",
                    "path" => "/metadata/dc.description.provenance/-",
                    "value" => array("value" => "Metadata updated on ".TODAY." by ".$reviewerOrFunctionName.": ".$report));

            $record['dc.description.provenance'][] = "Metadata updated on ".TODAY." by ".$reviewerOrFunctionName.": ".$report;
            
            $result = setDisplayFields($record);

            $record = $result['metadata'];
        
            $patches = array_merge($patches, $result['patch']);

            $patchJSON = json_encode($patches);
        
            $response = dspacePatchMetadata('items', $itemID, $patchJSON);
            
            $report .= '-- '.$response['status'].PHP_EOL;

            if($response['status'] == 'failed')
            {
                $status = 'failed';

                $report .= '-- patch metadata failed for '.$itemID.PHP_EOL.' -- REST API error response = '.print_r($response, TRUE).PHP_EOL.' -- Posted JSON was: '.$patchJSON.PHP_EOL;

                $errors[] = $report;
            }
            else
            {
                $status = 'modified';

                $itemJSON = $response['body'];
                    
                //save the sourceData in the database
                $result = saveSourceData($irts, 'dspace', $itemID, $itemJSON, 'JSON');
                
                //process the record
                $result = processDspaceRecord($itemJSON);

                $record = $result['record'];
                
                //save it in the database
                $result = saveValues('dspace', $itemID, $record, NULL);

                $result = saveValues('repository', $handle, $record, NULL);
            }
        }
        else
        {
            $status = 'unchanged';
        }
       
		return array('status' => $status, 'report' => $report, 'errors' => $errors, 'record' => $record);
	}