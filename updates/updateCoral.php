<?php

    
	//Define function to update coral records
	function updateCoralRelations($report, $errors, $recordTypeCounts)
	{
		global $irts;



		$token = loginToDSpaceRESTAPI();
		
		
		global $irts;

		$token = loginToDSpaceRESTAPI();

		//Get item ID or list of item IDs to check
		 if(isset($_GET['handle']))
		{
			$itemIDs = getValues($irts, "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND value LIKE'".$_GET['handle']."'AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
		}
		/* else
		{ 
			$itemIDs = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Specimen' AND `idInSource` NOT IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'display.relations' AND `deleted` IS NULL)", array('idInSource'), 'arrayOfValues');
		 } 
		
 */
		foreach($itemIDs as $itemID)
		{
			$recordTypeCounts['all']++;

			$report .= $itemID.PHP_EOL;
			echo $itemID.PHP_EOL;
			$skip = FALSE;
			

			$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);

			$metadata = dSpaceJSONtoMetadataArray($json);
			
			$simplemetadata = array();
			foreach($metadata as $key => $values)
			{
				foreach($values as $value)
				{
					if(is_string($value['value']))
					{
						$simplemetadata[$key] = $value['value'];
					}
					//elseif(is_array
				}
			}

			$recordTypeCounts['all']++;

			

			if(!$skip)
			{
					//echo $idInSource.PHP_EOL;

					$json = getItemMetadataFromDSpaceRESTAPI($itemID, $token);

					$metadata = dSpaceJSONtoMetadataArray($json);
					
					$simplemetadata = array();
					foreach($metadata as $key => $values)
					{ 
						foreach($values as $value)
						{
							if(is_string($value['value']))
							{
								$simplemetadata[$key] = $value['value'];
							}
							
						}
			        }				
					
					
						 
						if(!empty($metadata['dc.relation.issupplementto']))
						{
							$simplemetadata['display.relations'] = '';
							$supplementto = $metadata['dc.relation.issupplementto'];
							
							foreach ($supplementto as $supplementto)
							{
								$doi = str_replace('DOI:', '', $supplementto);
								
							    $doi =array_values(array_slice($doi, 0, 1));
							    //print_r($doi);
								
								foreach ($doi as $doi)
								{
									
									$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'), 'singleValue');
									//echo $handle.PHP_EOL;
									
									$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');
									//echo $type.PHP_EOL;
								
								    $citation = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.citation'), array('value'), 'singleValue');
									
										
									
								}
	
								if(isset($simplemetadata['display.relations']))
								{	
							     $simplemetadata['display.relations'] .='<b>Is Supplement To:</b> <br/> <ul> <li><i>['.$type.']</i> <br/> '.$citation.'. DOI: <a href="https://doi.org/'.$doi.'">'.$doi.'</a> HANDLE: <a href="http://hdl.handle.net/'.$handle.'">'.$handle.'</a></li></ul>';
								}  
							
								
							}
							//print_r($simplemetadata['display.relations']);
							$metadata['display.relations'][0]['value'] = $simplemetadata['display.relations'];
							ob_flush();
							flush();
						    set_time_limit(0); 
							
							
						} 
						
							
						 if(isset($simplemetadata['dc.relation.isSourceOf']))
						{
							$simplemetadata['display.relations'] = '';
							$SourceOf = $metadata['dc.relation.isSourceOf'];
							
							foreach ($SourceOf as $SourceOf)
							{
								if(isset($simplemetadata['display.relations']))
								{
									$simplemetadata['display.relations'] .= '<b>Is Source Of: </b> <ul> <li>[Nucleotide]<br/> GenBank:<a href="https://www.ncbi.nlm.nih.gov/nuccore/'.$simplemetadata['dc.relation.isSourceOf'].'">'.$simplemetadata['dc.relation.isSourceOf'].'</a></li></ul>';
									
									 
								}
							
							}
							
							
						$metadata['display.relations'][1]['value'] =$simplemetadata['display.relations'];	
							
						}
						
						if(isset($metadata['dc.relation.haspart']))
						{
							$simplemetadata['display.relations'] = '';
							$haspart = $metadata['dc.relation.haspart'];
							
							
							foreach ($haspart as $haspart)
							{
								$handleURL =  str_replace('Handle:', '',$haspart);	
							    $handle =array_values(array_slice($handleURL, 0, 1));
							    print_r($handle);
								
								foreach ($handle as $handle)
								{
									$id = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `value` LIKE  '$handle' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
									
									
			
									$title= getValues($irts, setSourceMetadataQuery('dspace', $id, NULL, 'dc.title'), array('value'), 'singleValue');
									
									
									$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($id, $token);
									
									if(is_string($bitstreams))
									{
										$bitstreams = json_decode($bitstreams, TRUE);
								        $bitstreamURL = '';
										
										foreach($bitstreams as $bitstream)
										{
											if($bitstream['bundleName'] === 'THUMBNAIL')
											{
												$bitstreamURL = '/bitstream/id/'.$bitstream['id'].'/'.$bitstream['name'];
												echo $bitstreamURL.PHP_EOL;
											}
										}
										
									}
									
									
										
									
								}
								
								sleep(5);
								
								if(isset($simplemetadata['display.relations']))
								{	
							     $simplemetadata['display.relations'] .='<b>Has Part :<li><u><a href="'.$handle.'"title= '.$title.'</a></u><a title="'.$title.'" href="'.$handle.'"target="_blank" rel="noopener">'.$title.'<img class="object-fit" src="'.$bitstreamURL.'"  alt="Thumbnail" width="55"</a></li></a></li><br>';
								}  
								
									
								
								
							}
							$metadata['display.relations'][2]['value'] = $simplemetadata['display.relations'];
							
							ob_flush();
							flush();
						    set_time_limit(0); 
							
							
							
										
										
						} 
						
						if(isset($metadata['display.relations']))
						//if(isset($metadata['display.summary']))
						{ 
							 //$metadata['display.summary'][0]['value'] = preg_replace('/[\n]+/','', trim($metadata['display.summary'][0]['value'])); 

							//$report .= $metadata['display.summary'][0]['value'];

							$recordTypeCounts['modified']++;
							
							
							$metadata = appendProvenanceToMetadata($itemID, $metadata, 'updateCoral Record_for_Display');
						    
							
							
							$json = prepareItemMetadataAsDSpaceJSON($metadata);
							
							
							
							$response = putItemMetadataToDSpaceRESTAPI($itemID, $json, $token);
						
							if(is_array($response))
							{
								print_r($response);
								
								/* $status = statusOfTokenForDSpaceRESTAPI($token);

								$status = json_decode($status, TRUE);

								if($status['authenticated'] === FALSE)
								{
									$token = loginToDSpaceRESTAPI();
								}

								$response = putItemMetadataToDSpaceRESTAPI($idInSource, $json, $token); */
							}
							
							ob_flush();
							set_time_limit(0);
						}	
						
						
					
			}
			
			
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
	