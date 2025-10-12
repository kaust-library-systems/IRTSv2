<?php	
	//Define function to process eBird JSON metadata for a single observation
	function processEbirdRecord($token, $checklistID, $json, &$report, &$recordTypeCounts)
	{
		global $irts;
		
		$source = 'ebird';
		
		//if coming from harvest script for reprocessing
		if(is_array($json))
		{
			$json = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
		}
		
		$recordType = saveSourceData($irts, $report, $source, $checklistID, $json, 'JSON');	
		$recordType = 'new checklist';

		//echo $response;
		$checklist = json_decode($json, true);

		foreach ($checklist['obs']  as $observation) 
		{
			$recordTypeCounts['all']++;
			$recordTypeCounts['new observation']++;
			
			$checklistMetadata = $checklist;
			unset($checklistMetadata['obs']);
			$observation = array_merge($observation, $checklistMetadata);
			
			$observationID = $observation['obsId'];
			$observationDateTime = $observation['obsDt'];
			$pieces = explode(" ",$observationDateTime);
			
			$observation['obsDt'] = $pieces[0];
			$observation['obsTime'] = $pieces[1];
			
			$observationDate = date_create($observation['obsDt']);
			$observationDate = date_format($observationDate, 'jS F Y');
			$Date_pieces = explode(" ",$observationDate);
			
			
			$observation['obsmonth'] = $Date_pieces[1];
			$observation['obsyear'] = $Date_pieces[2];			 
			
			//print_r($observation);
			
			//List of metadata fields in the current record
			$currentFields = array_keys($observation);
			
			foreach($observation as $field => $value)
			{
				if(is_string($value))
				{
					$place = 1;
					
					$rowID = mapTransformSave($source, $observationID, '', $field, '', $place, $value, NULL);						
				}
			}
			
			$metadata = array();
			
			$fields = array("ebird.checklist.id", "ebird.observation.id" , "ebird.location.id" , "dwc.occurrence.recordedBy", "dc.date.observed" , "ebird.observation.time" , "dwc.occurrence.individualCount" , "ebird.species.code" , "dc.description.notes" ,
			"kaust.presence.year" , "kaust.presence.month");

			foreach ($fields as $field) 
			{
				$query = setSourceMetadataQuery($source, $observationID, NULL, $field);
				
				//echo $query;
				
				$metadata[$field] = getValues($irts, $query, array('value'), 'singleValue');
			}
			
			$metadata['dwc.location.locality'] = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.title' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.location.id' AND `value` LIKE '".$metadata['ebird.location.id']."' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Location' AND `deleted` IS NULL))", array('value'), 'singleValue');			
			
			$metadata['dc.type'] = "Observation Record";
			
			$metadata['dc.date.issued'] = $metadata['dc.date.observed'];
			
			$fields = array("dwc.taxon.vernacularName", "dwc.taxon.scientificName", "dwc.taxon.family", "dwc.taxon.order" , "ebird.family.commonName");

			foreach ($fields as $field) {
				$metadata[$field] = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE '$field' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '".$metadata['ebird.species.code']."' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Species Summary' AND `deleted` IS NULL))", array('value'), 'singleValue');
			}
			
			$date = date_create($metadata['dc.date.observed']);
			$date = date_format($date, 'jS F Y');
			
			$metadata['dc.title'] = "Observation : ". $metadata['dwc.taxon.vernacularName']. " at " .$metadata['dwc.location.locality']. " on " . $date;
			//print_r($metadata);
			
			$metadata['display.summary'] = createObservationSummary($metadata);
			
			$item = prepareItemMetadataAsDSpaceJSON($metadata, FALSE);
			
			//echo $item;
			//collection id for test server 43675
			/* $response = postItemToDSpaceRESTAPI('43675', $item, $token);
			
			print_r($response);	
			 */
			if(!empty($metadata["dwc.taxon.vernacularName"]))
			{
				$response = postItemToDSpaceRESTAPI('43675', $item, $token);
			
				//print_r($response);			
			}
			else
			{
				echo PHP_EOL."eBird observation with new species code: ".$metadata['ebird.species.code'].PHP_EOL.'Observation ID'.$observationID.PHP_EOL.'Checklist ID:'.$checklistID;;
				//Species code has no species summary
				$to = IR_EMAIL;
				$subject = "eBird observation with new species code: ".$metadata['ebird.species.code'];

				$message = 'Observation ID'.$observationID.PHP_EOL.'Checklist ID:'.$checklistID;

				$headers = "From: " .IR_EMAIL. "\r\n";

				//Send
				mail($to,$subject,$message,$headers);
			} 
			//break;			
		}
		
		//Get checklist page HTML to find any images
		$checklistID = $checklist['subId'];		
		exec ( "curl -c /tmp/cookie_ebird https://ebird.org/view/checklist/".$checklistID);
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://ebird.org/view/checklist/".$checklistID,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_COOKIEFILE => "/tmp/cookie_ebird",
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_HTTPHEADER => array(
			"Accept: */*",
			"Cache-Control: no-cache",
			"Connection: keep-alive",
			"Host: ebird.org",
			"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5",
			"accept-encoding: gzip, deflate",
			"cache-control: no-cache"
			
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} 
		else 
		{
			//echo $response;

			$img_doc = new DOMDocument();

			libxml_use_internal_errors(TRUE); //disable libxml errors
			$img_doc->loadHTML($response);
			libxml_clear_errors(); //remove errors for yucky html
			$img_xpath = new DOMXPath($img_doc);

			//get all the div's with an media_id
			$img_row = $img_xpath->query("//div[@data-media-id]");
			
			//$img_row = $img_xpath->query("//div[contains(concat(@data-media-id, ' '), (@data-media-speciescode, ' ')]");

			if($img_row->length > 0)
			{
				foreach($img_row as $row)
				{				
					$recordTypeCounts['all']++;
					$recordTypeCounts['new image']++;
					
					//echo 'Media ID:'.$row->getAttribute('data-media-id').PHP_EOL;
					//echo 'Species Code:'.$row->getAttribute('data-media-speciescode').PHP_EOL;
					
					$ebirdSpeciesCode	= 	$row->getAttribute('data-media-speciescode');
					$ebirdMediaId = $row->getAttribute('data-media-id');
					
					$image_metadata = array();
					$image_metadata['ebird.location.id'] = $metadata['ebird.location.id'];
					$image_metadata['ebird.checklist.id'] = $checklistID;
					$image_metadata['ebird.species.code'] = $ebirdSpeciesCode;
					$image_metadata['ebird.media.id'] = $ebirdMediaId;
					$image_metadata['dc.type'] = "Image";
					$image_metadata['dc.date.issued'] = $metadata['dc.date.issued'];
					//$image_metadata['ebird.location.id'] = $metadata['ebird.location.id'];
					$image_metadata['dwc.location.locality'] = $metadata['dwc.location.locality'];
					$image_metadata['dc.contributor.photographer'] = $metadata['dwc.occurrence.recordedBy'];
			
					$image_fields = array("dwc.taxon.scientificName", "dwc.taxon.vernacularName", "dwc.taxon.family", "ebird.family.commonName");

					foreach ($image_fields as $image_field)
					{			
						$image_metadata[$image_field] = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE '$image_field' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '".$ebirdSpeciesCode."' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Species Summary' AND `deleted` IS NULL))", array('value'), 'singleValue');
					}				
					
					$image_metadata['dc.title'] = "Photo of ". $image_metadata['dwc.taxon.vernacularName']. " at " .$image_metadata['dwc.location.locality']. " on " . $date; 
					//print_r($image_metadata);				
					
					if(!empty($image_metadata["dwc.taxon.vernacularName"]))
					{
						$image_metadata['display.summary'] = createImageSummary($image_metadata);
						$imge_item = prepareItemMetadataAsDSpaceJSON($image_metadata, FALSE);			
						$img_response = postItemToDSpaceRESTAPI('43675', $imge_item, $token);
					
						//print_r($img_response);

						$img_response = json_decode($img_response, true);
						$itemID = $img_response['id'];
						
						$response = postBitstreamToDSpaceRESTAPI($itemID, "https://download.ams.birds.cornell.edu/api/v1/asset/".$image_metadata['ebird.media.id']."/320", $image_metadata['ebird.media.id'].'.jpg', 'Image from Macaulay Library ML'.$image_metadata['ebird.media.id'], 'THUMBNAIL', $token);

						//print_r($response);
					}
					else
					{
						//Species code has no species summary
						$to = IR_EMAIL;
						$subject = "eBird image with new species code: ".$image_metadata['ebird.species.code'];

						$message = 'Image ID'.$image_metadata['ebird.media.id'].PHP_EOL.'Checklist ID:'.$checklistID;

						$headers = "From: " .IR_EMAIL. "\r\n";

						//Send
						mail($to,$subject,$message,$headers);
					}
				}
			}
			else
			{
				echo 'Checklist ID '.$checklistID.' has no image.'.PHP_EOL;
			}		
		}
		return $recordType;
	}	