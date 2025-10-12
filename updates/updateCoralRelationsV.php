<?php

    
	//Define function to update coral records
	function updateCoralRelationsV($report, $errors, $recordTypeCounts)
	{
		
		global $irts;
		$response = dspaceGetStatus();

		//Log in
		$response = dspaceLogin();
		$report = '';
		$errors = array();
		$template = array(
			
			'Is Supplement To'=>'dc.relation.issupplementto',
			'Is Part Of'=>'dc.relation.ispartof',
			'Is Source Of'=>'dc.relation.isSourceOf',
			'Has Part'=>'dc.relation.haspart');
	

	

		//Get item ID or list of item IDs to check
		 if(isset($_GET['handle']))
		{
			$report .= 'Item Handle:'.$_GET['handle'].PHP_EOL;

			$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `idInSource` LIKE '".$_GET['handle']."' AND `deleted` IS NULL";

		}
		 else
		{ 
			$query="SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field`LIKE'dwc.occurrence.ID' AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.type' AND `value` LIKE 'Specimen' AND `deleted` IS NULL)";
		} 
		$Handles = getValues($irts, $query, array('idInSource'));
		 
		
	    foreach ($Handles as $itemHandle)
		{
			
			$search = dspaceGetItemByHandle($itemHandle);
			if($response['status'] == 'success')
			{
				$item = json_decode($search['body'], TRUE);
				$itemID = $item['id'];
				$report .= $itemID.PHP_EOL;
				echo $itemID.PHP_EOL;
				//$metadata =$item['metadata'];
				
				$recordTypeCounts['all']++;
				$Relation = '';
				//$metadata = []
				$relationDisplay = [];
				foreach($template as $label => $field)
				{
					/* if(!empty($metadata[$field]))
					{ */
						//$displaySnippet .= "<h5>$label</h5>";
						foreach($item['metadata'] as $key => $entries)
						{
							foreach($entries as $place => $value)
							{
								
							    if($key == 'dc.relation.issupplementto')	
								{
									$doi = str_replace('DOI: ', '', $value);
							        $doi =array_values(array_slice($doi, 0, 1));
							        print_r($doi.PHP_EOL);
							        //if(isset($doi))
								    foreach($doi as $doi)
									{
										$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.doi', $doi), array('idInSource'), 'singleValue');
										$type = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.type'), array('value'), 'singleValue');
										$citation = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.citation'), array('value'), 'singleValue');
									}
									$Relation ='<b>Is Supplement To:</b> <br/> <ul> <li><i>['.$type.']</i> <br/> '.$citation.'. DOI: <a href="https://doi.org/'.$doi.'">'.$doi.'</a> HANDLE: <a href="http://hdl.handle.net/'.$handle.'">'.$handle.'</a></li></ul>';
								    $relationDisplay[] = $Relation;
								}
								if($key== 'dc.relation.ispartof')	
								{
									$handleURL =  str_replace('Handle:', '',$value);	
									//$handle =array_values(array_slice($handleURL, 0, 1));
									foreach ($handle as $handle)
									{
										$id = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `value` LIKE  '$handle' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
										$title= getValues($irts, setSourceMetadataQuery('dspace', $id, NULL, 'dc.title'), array('value'), 'singleValue');
										$type = getValues($irts, setSourceMetadataQuery('dspace', $id, NULL, 'dc.type'), array('value'), 'singleValue');
							        }
								    $Relation ='<b>Is Part Of:</b> <br/> <ul> <li><i>['.$type.']</i> <br/> '.$title.'. HANDLE: <a href="http://hdl.handle.net/'.$handle.'">'.$handle.'</a></li></ul>';
								    $relationDisplay[] = $Relation;
							    }
							    if($key== 'dc.relation.isSourceOf')	
							    {
									$Relation= '<b>Is Source Of: </b> <ul> <li>[Nucleotide]<br/> GenBank:<a href="https://www.ncbi.nlm.nih.gov/nuccore/'.$value['value'].'">'.$value['value'] .'</a></li></ul>';
								    $relationDisplay[] = $Relation;
								}
								if($key== 'dc.relation.haspart')	
								{
									$handleURL =  str_replace('Handle:', '',$value);	
									$handle =array_values(array_slice($handleURL, 0, 1));
									foreach ($handle as $handle)
									{
										$search = dspaceGetItemByHandle($handle);
										$item = json_decode($search['body'], TRUE);
										$id  = $item['id']; 
										$title = $item['name'];
										$ItemBundles = dspaceListItemBundles($id);	
										$ItemBundles = json_decode($ItemBundles['body'], TRUE);
										foreach($ItemBundles['_embedded']['bundles'] as $ItemBundle)
										{
											$ItemBundlesName = $ItemBundle['name'];
											$ItemBundleID = $ItemBundle['uuid'];
											if ($ItemBundlesName == 'THUMBNAIL')
											{
												$bundleBitstreams = dspaceListBundlesBitstreams($ItemBundleID);
												$bundleBitstreams = json_decode($bundleBitstreams['body'], TRUE);  
												$BitstreamID = $bundleBitstreams['_embedded']['bitstreams'][0]['id'];
												$bitstreamURL ='https://kaust7-test.atmire.com/server/api/core/bitstreams/'.$BitstreamID.'/content';
											}
										}
									}
									$Relation= '<b>Has Part :<li><u><a href="'.$handle.'" title="'.$title.'"></a></u><a title="'.$title.'" href="'.$handle.'" target="_blank" rel="noopener">'.$title.'<img src='.$bitstreamURL.' alt="Thumbnail" width="55"></a></li></a></li><br>';
									$relationDisplay[] = $Relation;
								}
							
							}	
						}
					/* }		 */
				}
					
				
				print_r($relationDisplay);
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
				print_r($response);
				echo $response['status'].PHP_EOL;
				if($response['status'] == 'failed')
				{
					print_r($response);

					print_r($patchJSON);
				}  
				set_time_limit(0);
				ob_flush();

				sleep(5);
			
				

			}
			else
			{
				print_r($response);
					
				sleep(5);
			}
			
			
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
	