<?php



//--------------------------------------------------------------------------------------------

function PostToDspace($accessionNumber, $token, $record, &$recordTypeCounts){
	
	

	global $irts, $errors;$report;

	$report = '';
	$json  ='';
	
	$typeCollectionID = '39708';
	
	$fields = array("dc.identifier.biosample" ,"dc.title", "dc.type" ,"dc.publisher" , "dc.date.issued" , "dc.date.submitted" , "dc.date.updated" ,"dwc.location.decimalLatitude","dwc.location.decimalLongitude","dc.relation.ispartof","dc.relation.url");
	
	
	foreach ($fields as $field)
		{
			$metadata[$field] = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'ncbi'
			AND `idInSource` = '$accessionNumber' AND `field` LIKE '$field' AND `deleted` IS NULL", array('value'), 'singleValue');
				
		} 
	$info = postDspace($accessionNumber, $metadata, $typeCollectionID, $fields ,$token, $recordTypeCounts);
	
	$itemID = $info['itemID'];
	
	
			 
			
			$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);
			if(is_string($json))
			{
				$record = json_decode($json, TRUE);

				$record = dSpaceMetadataToArray($record);
				
				$result = setDisplayRelationsField($record);
				print $result;
							
				if(in_array($result['status'], array('new','changed')))
				{
					//update the metadata for the related record in DSpace
					$record = $result['record'];
					
					$record = appendProvenanceToMetadata($itemID, $record, __FUNCTION__);

					$json = prepareItemMetadataAsDSpaceJSON($record);
					
					sleep(5);

					$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
					
					$recordTypeCounts['modified']++;

					echo ' - modified'.PHP_EOL;
					$report .= ' - modified'.PHP_EOL;
				}
				else
				{
					$recordTypeCounts['unchanged']++;

					echo ' - unchanged'.PHP_EOL;
					$report .= ' - unchanged'.PHP_EOL;
				}
				
			//save it in the database
			$recordType = processDspaceRecord($itemID, $json, $report);
			
			
			
			//set inverse relationships on any related items
			$result = setInverseRelations($itemID);
		
			//$recordTypeCounts['new']++;
				
			}
			sleep(5);
			ob_flush();
		}
		
