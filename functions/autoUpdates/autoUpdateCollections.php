<?php	
	//Define function to update repository collections
	function autoUpdateCollections($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$coeCommunityId = getValues($irts, "SELECT idInSource FROM `metadata` 
			WHERE `source` LIKE 'dspace' 
			AND `field` LIKE 'dspace.name' 
			AND value LIKE 'Centers of Excellence'
			AND deleted IS NULL", 
			array('idInSource'), 
			'singleValue');
		
		if (empty($coeCommunityId))
		{
			$Community = 
				'{
				  "name": "Centers of Excellence",
				  "metadata": {
				    "dc.title": [
				      {
				        "value": "Centers of Excellence",
				        "language": null,
				        "authority": null,
				        "confidence": -1
				      }
				    ]
				  }
				}';
				
			$response = dspaceCreateCommunity($Community);
			
			if ($response['status'] = 'success')
			{
				$Community = json_decode($response['body'], TRUE);
				
				$coeCommunityId = $Community['id'];
				
				$coeCommunityHandle = $Community['handle'];
				
				$status = 'modified';

        $itemJSON = $response['body'];
            
        //save the sourceData in the database
        $result = saveSourceData($irts, 'dspace', $coeCommunityId, $itemJSON, 'JSON');
        
        //process the record
        $result = processDspaceRecord($itemJSON);

        $record = $result['record'];
        
        //save it in the database
        $result = saveValues('dspace', $coeCommunityId, $record, NULL);

        $result = saveValues('repository', $coeCommunityHandle, $record, NULL);
			}
			else
			{
				print_r ($response);
			}
		}
		
		//get Org Ids of Centers of Exellents
		$orgIds = getValues($irts, "SELECT idInSource FROM `metadata` 
			WHERE `source` LIKE 'local' 
			AND `field` LIKE 'local.org.type' 
			AND value LIKE 'centerOfExcellence'
			AND idInSource NOT IN (SELECT idInSource FROM `metadata` WHERE `source` LIKE 'local' AND `field` LIKE 'dspace.collection.handle' AND deleted IS NULL)
			AND deleted IS NULL", 
			array('idInSource'), 
			'arrayOfValues');
			
		foreach ($orgIds as $orgId)
		{
			$name = getValues($irts, "SELECT value FROM `metadata` 
				WHERE `source` LIKE 'local' 
				AND `field` LIKE 'local.org.name' 
				AND idInSource LIKE '".$orgId."'
				AND deleted IS NULL", 
				array('value'), 
				'singleValue');
			
			$Collection = 
				'{
				  "name": "'.$name.'",
				  "metadata": {
				    "dc.title": [
				      {
				        "value": "'.$name.'",
				        "language": null,
				        "authority": null,
				        "confidence": -1
				      }
				    ]
				  }
				}';
			
			$response = dspaceCreateCollection($coeCommunityId, $Collection);
			
			if ($response['status'] = 'success')
			{
				$Collection = json_decode($response['body'], TRUE);
				
				$coeCollectionId = $Collection['id'];
				
				$coeCollectionHandle = $Collection['handle'];
				
				$status = 'modified';

        $itemJSON = $response['body'];
            
        //save the sourceData in the database
        $result = saveSourceData($irts, 'dspace', $coeCollectionId, $itemJSON, 'JSON');
        
        //process the record
        $result = processDspaceRecord($itemJSON);

        $record = $result['record'];
        
        //save it in the database
        $result = saveValues('dspace', $coeCollectionId, $record, NULL);
        
        $result = saveValue('local', $orgId, 'dspace.collection.handle', '0', $coeCollectionHandle, NULL);

        $result = saveValues('repository', $coeCollectionHandle, $record, NULL);
			}
			else
			{
				print_r ($response);
			}
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}