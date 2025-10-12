<?php

    
	function updateCoralRelations($report, $errors, $recordTypeCounts)
	{
		
		global $irts;
		$response = dspaceGetStatus();

		//Log in
		$response = dspaceLogin();
		$report = '';
		$errors = array();
	    $template = array(
			
			'dc.relation.issupplementto'=>'Is Supplementto',
			'dc.relation.ispartof'=>'Is Part Of',
			'dc.relation.isSourceOf'=>'Is Source Of',
			'dc.relation.haspart'=>'Has Part');
	

		//Get item ID or list of item IDs to check
		 if(isset($_GET['handle']))
		{
		

			$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE 
			`source` LIKE 'repository' AND `idInSource` LIKE '".$_GET['handle']."' 
			AND `deleted` IS NULL";

		}
		 else
		{ 
			$query = "SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND 
			`field` LIKE 'dc.type' AND `value` LIKE 'Specimen'";
			//"SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field`LIKE'dwc.occurrence.ID' AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.type' AND `value` LIKE 'Specimen' AND `deleted` IS NULL)";
		} 
		$Handles = getValues($irts, $query, array('idInSource'));
		 
		
	    foreach ($Handles as $itemHandle)
		{
			$report .= 'Item Handle:'.$itemHandle.PHP_EOL;
			
			$search = dspaceGetItemByHandle($itemHandle);
			if($search ['status'] == 'success')
			{
				$item = json_decode($search['body'], TRUE);
				$itemID = $item['id'];
                $report .= 'Item UUID:'.$itemID.PHP_EOL;
				echo $itemID.PHP_EOL;
				$itemMetadata =$item['metadata'];
				$record = dSpaceMetadataToArray($itemMetadata);
				$Relation = '';
				$relationDisplay = [];
				if(!isset($record['display.details.right']))
				{
					$record['display.details.right'] = '';
					foreach($template as $field => $label)
					{
						if(!empty($record[$field]))
						{
							$value = $record[$field];
							foreach($value as $place => $value)
							{
								$value= strtolower($value);
								$value= preg_replace('/\s+/', '', $value);
								if($field == 'dc.relation.issupplementto')	
								{
									$doi = str_replace('doi:', '', $value);
									if(isset($doi))
									{
										$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'), 'singleValue');
										$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');
										$citation = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.citation'), array('value'), 'singleValue');
									}
									$Relation = '<b>Is Supplement To:</b> <br/> <ul> <li><i>['.$type.']</i> <br/> '.$citation.' DOI: <a href="https://doi.org/'.$doi.'">'.$doi.'</a> HANDLE: <a href="http://hdl.handle.net/'.$handle.'">'.$handle.'</a></li></ul>';
									$relationDisplay[] = $Relation;
								}
								if($field == 'dc.relation.ispartof')	
								{
									$handle =  str_replace('Handle:', '',$value);	
									if(isset($handle))
									{
										$id = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `value` LIKE  '$handle' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
										$title= getValues($irts, setSourceMetadataQuery('dspace', $id, NULL, 'dc.title'), array('value'), 'singleValue');
										$type = getValues($irts, setSourceMetadataQuery('dspace', $id, NULL, 'dc.type'), array('value'), 'singleValue');
									}
									$Relation='<b>Is Part Of:</b> <br/> <ul> <li><i>['.$type.']</i> <br/> '.$title.'. HANDLE: <a href="http://hdl.handle.net/'.$handle.'">'.$handle.'</a></li></ul>';
									$relationDisplay[] = $Relation;
								}
								if($field == 'dc.relation.isSourceOf')	
								{
									$Relation='<b>Is Source Of: </b> <ul> <li>[Nucleotide]<br/> GenBank:<a href="https://www.ncbi.nlm.nih.gov/nuccore/'.$value.'">'.$value .'</a></li></ul>';
									$relationDisplay[] = $Relation;
								}
								if($field == 'dc.relation.haspart')	
								{
									$handle=  str_replace('handle:', '',$value);
									if(isset($handle))
									{
										$search = dspaceGetItemByHandle($handle);
										if($search ['status'] == 'success')
										{
											$item = json_decode($search['body'], TRUE);
											$id  = $item['id']; 
											$title = $item['name'];
											$ItemBundles = dspaceListItemBundles($id);	
											$ItemBundles = json_decode($ItemBundles['body'], TRUE);
											foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
											{
												$ItemBundlesName = $ItemBundle['name'];
												$ItemBundleID = $ItemBundle['uuid'];
												/* if ($ItemBundlesName == 'THUMBNAIL')
												{
													$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
													$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);  
													$BitstreamID = $bundleBitstreams['_embedded']['bitstreams'][0]['id'];
													//$bitstreamURL = 'http://'.REPOSITORY_BASE_URL.'/server/api/core/bitstreams/'.$BitstreamID.'/content';
												} */
											}
										}
									}
									//$Relation ='<b>Has Part :</b><li><u><a href="'.$handle.'" title="'.$title.'"></a></u><a title="'.$title.'" href="'.$handle.'" target="_blank" rel="noopener">'.$title.'<img src='.$bitstreamURL.' alt="Thumbnail" width="55"></a></li></a></li><br>';
								    $Relation ='<b>Has Part :</b><li><u><a href="'.$handle.'" title="
								    '.$title.'"></a></u><a title="'.$title.'" href="'.$handle.'" target=
								     "_blank" rel="noopener">'.$title.'</a></li></a></li><br>';  
								    $relationDisplay[] = $Relation;    
						        }
					        }		
				        }
			        }
					//print_r($relationDisplay);
					$patches = [];
					if(!empty($relationDisplay))
					{
						foreach($relationDisplay as $place => $relationDisplay)
						{
							$patches[] = array("op" => "add",
							"path" => "/metadata/display.details.right/-",
							"value" => array("value" => $relationDisplay));
						}
					}
					
					$patchJSON = json_encode($patches);		
				    $response = dspacePatchMetadata('items', $itemID, $patchJSON);
					if($response['status'] == 'success')
					{
						$report .= $patchJSON;
					    $recordTypeCounts['modified']++;
					}
						
					if($response['status'] == 'failed')
					{
						$report .= 'failed'.PHP_EOL.$response.PHP_EOL;
						$report .= $patchJSON.PHP_EOL;
					
					}  
					set_time_limit(0);
					ob_flush();
					sleep(10); 
				}
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
