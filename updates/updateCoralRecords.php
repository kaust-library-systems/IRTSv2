<?php
	//Define function to update coral records
	function updateCoralRecords($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();
		$report = '';
		$errors = array();
		$patch = [];
	
		//$token = loginToDSpaceRESTAPI();

		$template = array(
			
			'dc.type'=>'Type',
			'dwc.taxon.family'=>'Family',
			'dwc.taxon.genus'=>'Genus',
			'dwc.taxon.vernacularName'=>'Species',
			'dwc.location.locality'=>'Location',
			'dc.date.collected'=>'Date Collected',
			'dwc.location.decimalLatitude'=>'Latitude',
			'dwc.location.decimalLongitude'=>'Longitude');
			
		$facets = array(
		
		'dwc.location.locality'=>'location');
		
		$Species = array(
		'dwc.taxon.vernacularName'=>'species');
		
		$recordTypeCounts['all'] = 1;

	
		 
		 
		 //Get item ID or list of item IDs to check
		 if(isset($_GET['handle']))
		{
			//$report .= 'Item Handle:'.$_GET['handle'].PHP_EOL;

			$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `idInSource` LIKE '".$_GET['handle']."' AND `deleted` IS NULL";

		}
		else
		{
			$query ="SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND 
			`field` LIKE 'dc.type' AND `value` LIKE 'Specimen'";
		
		}	
			 
		$Handles = getValues($irts, $query, array('idInSource'));
		 
		foreach ($Handles as $itemHandle)
		{
			$report .= 'Item Handle:'.$itemHandle.PHP_EOL;
			$search = dspaceGetItemByHandle($itemHandle);
			$item = json_decode($search['body'], TRUE);
		    $itemID = $item['id'];
			echo $itemID.PHP_EOL;
			$itemMetadata =$item['metadata'];
			$record = dSpaceMetadataToArray($itemMetadata);
		
			
			$simplemetadata = array();
			foreach($itemMetadata as $key => $values)
			{
				foreach($values as $value)
				{
					
					$simplemetadata[$key] = $value['value'];
							
				}
			}

			
			if(!isset($simplemetadata['display.details.left']))
			{	
		       
			   $simplemetadata['display.details.left'] = '';
				foreach($template as $field => $label)
				{
					if(isset($simplemetadata[$field]))
					{
						
						$value = $simplemetadata[$field];							
						$simplemetadata['display.details.left'] .= "<h5>$label</h5>";
						
						if(in_array($field, array_keys($facets)))
						{
							$simplemetadata['display.details.left'] .= '<p><a href="/search?f.'.$facets[$field].'='.$value.',equals&spc.page=1">'.$value.'</a></p>';
						}
						
						elseif(in_array($field, array_keys($Species)))
						{
							if(isset($simplemetadata['dwc.specific.epithet']))
							{
								$simplemetadata['display.details.left'] .= '<p><a href="/search?f.'.$Species[$field].'='.$value.',equals&spc.page=1">'.$simplemetadata['dwc.specific.epithet'].'</a></p>';
							}
							
							else
							{
								$simplemetadata['display.details.left'] .= '<p><a href="/href="/search?f.='.$Species[$field].'='.$value.',equals&spc.page=1">'.$value.'</a></p>';
							}
						}
						else
						{
							$simplemetadata['display.details.left'] .= "<p>$value</p>";
						}
									
									
					}
				}
				print_r($simplemetadata['display.details.left'].PHP_EOL);
				//$itemMetadata['display.details.left'][0]['value'] = $simplemetadata['display.details.left'];
				
				$simplemetadata['display.details.left'] = preg_replace('/[\n]+/','', trim($simplemetadata['display.details.left']));
				$patches = [];
			
				
				if(!empty($simplemetadata['display.details.left']))
				{
					$patches[] = array("op" => "add",
						"path" => "/metadata/display.details.left/0",
						"value" => array("value" => $simplemetadata['display.details.left']));
					
				}
			
		

				$patchJSON = json_encode($patches);		
				$response = dspacePatchMetadata('items', $itemID, $patchJSON);
				
				$report .= $patchJSON;
				$recordTypeCounts['modified']++;				
				
				
				if($response['status'] == 'success')
				{
					$report .= $simplemetadata['display.details.left'];
					$recordTypeCounts['modified']++;
					//$record = appendProvenanceToMetadata($itemID, $itemMetadata,'updateCoralRecord-forDisplay');
					
				}
				if($response['status'] == 'failed')
				{
					print_r($response);
				}  
				set_time_limit(0);
				ob_flush();	
				
				/* if(isset($simplemetadata['display.details.left']))
				{
					$simplemetadata['display.details.left']= preg_replace('/[\n]+/','', trim($simplemetadata['display.details.left']));
					
					
					
					//processes updating existing records will include the display fields in the patch
					$patch[] = array("op" => "add",
					"path" => "/metadata/display.details.left/0",
					"value" => array("value" => $simplemetadata['display.details.left']));

					$patchJSON = json_encode($patch);
						
					$response = dspacePatchMetadata('items', $itemID, $patchJSON);
					if($response['status'] == 'success')
					{
						$report .= $itemMetadata['display.details.left'][0]['value'];
						$recordTypeCounts['modified']++;
					//$record = appendProvenanceToMetadata($itemID, $itemMetadata,'updateCoralRecord-forDisplay');
					
					}
					if($response['status'] == 'failed')
					{
						print_r($response);
					}

					ob_flush();
					set_time_limit(0);
				} */  
			}
					
			ob_flush();
			set_time_limit(0);
			sleep(5);
			
		

		}
		

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
		
	}

					