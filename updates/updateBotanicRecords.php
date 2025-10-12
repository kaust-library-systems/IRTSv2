<?php
	//Define function to update Botany records
	function updateBotanicRecords($report, $errors, $recordTypeCounts)
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
			
			'dc.type'=>'Plant Group',
			'ebird.family.commonName'=>'Family',
			'dwc.specific.epithet'=>'Species');
			
		$facets = array(

		'dc.type'=>'Plant Group');
		
		$family = array('ebird.family.commonName'=>'Family');
		
		
		$recordTypeCounts['all'] = 1;

	
		if(isset($_GET['handle']))
		{
		

			$baseQuery = 'dc.identifier.uri:http://hdl.handle.net/'.$_GET['handle']."'";
		}
		else
		{ 
			
			
			$baseQuery = 'scope=90dd105f-b426-46d2-a597-b4e8004b460a';
			//"SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.type' AND `value` LIKE 'Specimen'AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'display.details.left' AND `deleted` IS NULL)";
		 } 
		 
		 
		 $page = 0;
		//continue paging until no further results are returned
		$continuePaging = TRUE;
		while($continuePaging)
		{
			if(!empty($page))
			{
				$query = $baseQuery.'&page='.$page;
			}
			else
			{
				$query = $baseQuery;
			}
			
			$Search = dspaceSearch($query);
			$results = json_decode($Search['body'], TRUE);
			$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];
			
			foreach($results['_embedded']['searchResult']['_embedded']['objects']as $result)
			{ 
			
				$item = $result['_embedded']['indexableObject'];
				$uuid = $item['uuid'];
				$metadata =$item['metadata'];
				$record = dSpaceMetadataToArray($metadata);
				//print_r($record);
				foreach($metadata as $key => $values)
				{
					foreach($values as $value)
					{
						if(is_string($value['value']))
						{
							$simplemetadata[$key] = $value['value'];
						}
					}
					if(!isset($simplemetadata['display.details.left']))
					{
						$simplemetadata['display.details.left'] = '';
						foreach($template as $field => $label)
						{
							if(!empty($simplemetadata[$field]))
							{
								$value = $simplemetadata[$field];							
								$simplemetadata['display.details.left'] .= "<h5>$label</h5>";
								if(in_array($field, array_keys($facets)))
								{
									$simplemetadata['display.details.left'] .= '<p><a href="/search?f.'.$facets[$field].'='.$value.',equals&spc.page=1">'.$simplemetadata['dc.type'].'</a></p>';
								}
								elseif(in_array($field,array_keys($family)))
								{
									$simplemetadata['display.details.left'] .= '<p><a href="/search?f.'.$family[$field].'='.$value.',equals&spc.page=1">'.$simplemetadata['ebird.family.commonName'].'</a></p>';
								}
								else
								{
									$simplemetadata['display.details.left'] .= "<p>$value</p>";
								}
						    }								
				        }
						
						$metadata['display.details.left'][0]['value'] = $simplemetadata['display.details.left'];
						if(isset($metadata['display.details.left']))
						{
							print_r( $metadata['display.details.left'][0]['value']);
							$metadata['display.details.left'][0]['value'] = preg_replace('/[\n]+/','', trim($metadata['display.details.left'][0]['value']));
							$report .= $metadata['display.details.left'][0]['value'];
							$recordTypeCounts['modified']++;
							$record = appendProvenanceToMetadata($uuid, $metadata,'updateBotanicRecord-forDisplay');
								//processes updating existing records will include the display fields in the patch
							$patch[] = array("op" => "add",
							"path" => "/metadata/display.details.left/0",
							"value" => array(array("value" => $simplemetadata['display.details.left'])));
							$patchJSON = json_encode($patch);
							$response = dspacePatchMetadata('items', $uuid, $patchJSON);
							ob_flush();
							set_time_limit(0);
						}  
					}
				}	
			}
				
				
			if(!isset($results['_embedded']['searchResult']['_links']['next']))
			{
				$continuePaging = FALSE;
			}
			else
			{
				$page++;
				if($page >= $totalPages)
				{
					$continuePaging = FALSE;
						
						
				}
			}
		}
		
		
		

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
		
	}

					