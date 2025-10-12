<?php
	//Define function to update bird records
	function updateBirdRecords($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$token = loginToDSpaceRESTAPI();
		
		//print_r($token).PHP_EOL;
		
		//$type = 'Image';
		//$type = 'Species Summary';
		//$type = 'Observation Record';
		$type = 'Location';
		
		//Update all of given type
		$result = $irts->query("SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE '$type' AND deleted IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.location.id' AND `value` = '' AND `deleted` IS NULL)");
		
		//Update all of given type
		//$result = $irts->query("SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE '$type' AND deleted IS NULL");

		//Update new only of given type
		//$result = $irts->query("SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE '$type' AND `idInSource` NOT IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'display.summary' AND `deleted` IS NULL)");
		
		//Update all new
		//$result = $irts->query("SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dwc.taxon.vernacularName' AND deleted IS NULL AND `idInSource` NOT IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'display.summary' AND `deleted` IS NULL)");
		
		//Specific item
		//$result = $irts->query("SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `value` LIKE 'http://hdl.handle.net/123456789/654019' AND `deleted` IS NULL");		 

		while($row = $result->fetch_assoc())
		{
			$skip = FALSE;

			$idInSource = $row['idInSource'];

			echo $idInSource.PHP_EOL;

			$json = getItemMetadataFromDSpaceRESTAPI($idInSource, $token);

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

			//print_r($metadata);

			/* if(isset($metadata['display.summary']))
			{
				$skip = TRUE;
			} */

			if(!$skip)
			{
				if($metadata['dc.type'][0]['value']==='Species Summary')
				{
					$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($idInSource, $token);

					if(is_string($bitstreams))
					{
						$bitstreams = json_decode($bitstreams, TRUE);

						$bitstreamURL = '';

						foreach($bitstreams as $bitstream)
						{
							if($bitstream['bundleName'] === 'THUMBNAIL')
							{
								$bitstreamURL = '/bitstream/id/'.$bitstream['id'].'/'.$bitstream['name'];
							}
						}
					}

					$metadata['display.summary'][0]['value'] = createSpeciesSummary($metadata, $bitstreamURL);
				}
				elseif($metadata['dc.type'][0]['value']==='Observation Record')
				{
					$metadata['dc.title'][0]['value'] = "Observation : ". $simplemetadata['dwc.taxon.vernacularName']. " at " .$simplemetadata['dwc.location.locality']. " on " . $simplemetadata['dc.date.issued'];					
					
					$metadata['display.summary'][0]['value'] = createObservationSummary($simplemetadata);
				}
				elseif($metadata['dc.type'][0]['value']==='Image')
				{
					//print_r($metadata);
					
					$metadata['dc.title'][0]['value'] = "Photo of ". $simplemetadata['dwc.taxon.vernacularName']. " at " .$simplemetadata['dwc.location.locality']. " on " . $simplemetadata['dc.date.issued']; 
					
					$metadata['display.summary'][0]['value'] = createImageSummary($simplemetadata);
				}
				elseif($metadata['dc.type'][0]['value']==='Location')
				{
					if(isset($metadata['ebird.location.id']))
					{
						if(empty($metadata['ebird.location.id'][0]['value']))
						{
							unset($metadata['ebird.location.id']);
						}
					}
					
					//Start creating display summary
					$metadata['display.summary'][0]['value'] = '';

					if(isset($metadata['dc.description.notes']))
					{
						$metadata['display.summary'][0]['value'] .= '<p>'.$metadata['dc.description.notes'][0]['value'].'</p>';
					}

					if(isset($metadata['ebird.location.id']))
					{
						$bookletID = getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.location.id' AND `value` LIKE '".$metadata['ebird.location.id'][0]['value']."' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Booklet' AND `deleted` IS NULL)", array('idInSource'), 'singleValue');

						$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($bookletID, $token);

						if(is_string($bitstreams))
						{
							$bitstreams = json_decode($bitstreams, TRUE);

							$thumbnailID = '';
							$thumbnailName = '';
							$originalID = '';
							$originalName = '';

							foreach($bitstreams as $bitstream)
							{
								if($bitstream['bundleName'] === 'THUMBNAIL')
								{
									$thumbnailID = $bitstream['id'];
									$thumbnailName = $bitstream['name'];
								}
								elseif($bitstream['bundleName'] === 'ORIGINAL')
								{
									$originalID = $bitstream['id'];
									$originalName = $bitstream['name'];
								}
							}

							if(!empty($originalID)&&!empty($thumbnailID))
							{
								$metadata['display.summary'][0]['value'] .= '<a href="/bitstream/id/'.$originalID.'/'.$originalName.'"><img title="'.$metadata['dc.title'][0]['value'].' Self Guided Bird Walk Booklet" class="object-fit" src="/bitstream/id/'.$thumbnailID.'/'.$thumbnailName.'" alt="Thumbnail" /><h5>'.$metadata['dc.title'][0]['value'].' Self Guided Bird Walk Booklet</h5></a>';
							}
						}

						$metadata['display.summary'][0]['value'] .= '<h5><a href="https://ebird.org/printableList?regionCode='.$metadata['ebird.location.id'][0]['value'].'">Printable checklist, generated by eBird.</a></h5>';

						$metadata['display.summary'][0]['value'] .= '<h5><a title="Checklist including charts of species frequency, combined with images and audio when available" href="https://ebird.org/hotspot/'.$metadata['ebird.location.id'][0]['value'].'/media">Illustrated checklist, generated by eBird.</a></h5>';
					}
					$metadata['display.summary'][0]['value'] .= '<h5><a href="/handle/10754/632257/discover?view=grid&rpp=12&filtertype_0=location&filter_0='.$metadata['dc.title'][0]['value'].'&filter_relational_operator_0=equals&filtertype=type&filter_relational_operator=equals&filter=Image&sort_by=dc.date.issued_dt&order=desc">Search bird photos taken at the '.$metadata['dc.title'][0]['value'].'.</a></h5>';

					$metadata['display.summary'][0]['value'] .= '<h5><a href="/handle/10754/632257/discover?view=grid&rpp=12&filtertype_0=location&filter_0='.$metadata['dc.title'][0]['value'].'&filter_relational_operator_0=equals&filtertype=type&filter_relational_operator=equals&filter=Species+Summary&sort_by=dc.title_sort&order=asc">Search all species seen at the '.$metadata['dc.title'][0]['value'].'.</a></h5>';
					
					$metadata['display.summary'][0]['value'] .= '<h5><a href="/handle/10754/632257/discover?filtertype_0=location&filter_0='.$metadata['dc.title'][0]['value'].'&filter_relational_operator_0=equals&filtertype=type&filter_relational_operator=equals&filter=Observation+Record&sort_by=dc.title_sort&order=asc">Search all observations made at the '.$metadata['dc.title'][0]['value'].'.</a></h5>';
					
					if(isset($metadata['ebird.location.id']))
					{
						$metadata['display.summary'][0]['value'] .= '<br>
						<b>Submitting observations from this location:</b>
						<br>
						You can contribute your own bird observations via the eBird system by clicking the button below. You will first have to create an account with eBird, but will then be able to enter observations that will show in eBird and will also be used to update the KAUST bird records collection.
						<br>
						<a href="https://ebird.org/submit/effort?locID='.$metadata['ebird.location.id'][0]['value'].'&clr=1" class="btn btn-primary" target="_blank">Submit Bird Observations at the '.$metadata['dc.title'][0]['value'].' via eBird</a>';
					}
				}

				if(isset($metadata['display.summary']))
				{
					$metadata['display.summary'][0]['value'] = preg_replace('/[\n]+/','', trim($metadata['display.summary'][0]['value']));

					$report .= $metadata['display.summary'][0]['value'];

					//$recordTypeCounts['modified']++;
					
					$metadata = appendProvenanceToMetadata($idInSource, $metadata, 'updateBirdRecord-forDisplay');

					$json = prepareItemMetadataAsDSpaceJSON($metadata);

					$response = putItemMetadataToDSpaceRESTAPI($idInSource, $json, $token);

					if(is_array($response))
					{
						$status = statusOfTokenForDSpaceRESTAPI($token);

						$status = json_decode($status, TRUE);

						if($status['authenticated'] === FALSE)
						{
							$token = loginToDSpaceRESTAPI();
						}

						$response = putItemMetadataToDSpaceRESTAPI($idInSource, $json, $token);
					}

					ob_flush();
					set_time_limit(0);
				}
			}
			//sleep(1);
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
