<?php	
	//Define function to prepare the display summary for a species
	function createSpeciesSummary($metadata, $bitstreamURL)
	{
		global $irts;

		$statuses = array('Breeding resident'=>'Known to have bred at KAUST','Escapes'=>'Birds seen may have been captive birds that escaped','Invasive species'=>'Species is not native to the area, but has become resident','Passage migrant'=>'Stops at KAUST during migratory periods','Summer visitor'=>'Visits KAUST in the summer','Resident in area'=>'Resident in surrounding area but not established within KAUST','Vagrant'=>'Outside of expected range','Winter visitor'=>'Visits KAUST in the winter');

		$months = array('Jan'=>'January', 'Feb'=>'February', 'Mar'=>'March', 'Apr' => 'April', 'May'=>'May', 'Jun'=>'June', 'Jul'=>'July', 'Aug'=>'August', 'Sep'=>'September', 'Oct'=>'October', 'Nov'=>'November', 'Dec'=>'December');
		
		$species = $metadata['ebird.species.code'][0]['value'];
		
		//echo $species.PHP_EOL;

		$handle = str_replace('http://hdl.handle.net/', '', $metadata['dc.identifier.uri'][0]['value']);

		//echo $handle.PHP_EOL;

		$metadata['display.summary'][0]['value'] = '';

		if(isset($metadata['dc.description.notes']))
		{
			$metadata['display.summary'][0]['value'] .= '<p>'.$metadata['dc.description.notes'][0]['value'].'</p>';
		}

		$metadata['display.summary'][0]['value'] .= '<table style="table-layout: fixed; width: 100%; margin-left: auto; margin-right: auto;">
		<tbody>';

		$metadata['display.summary'][0]['value'] .= '<tr>
		<td style="vertical-align: top; width: 50%;">';

		$metadata['display.summary'][0]['value'] .= '<p><h5>'.$metadata['dwc.taxon.vernacularName'][0]['value'].' (<em>'.$metadata['dwc.taxon.scientificName'][0]['value'].'</em>)<a href="https://ebird.org/species/'.$metadata['ebird.species.code'][0]['value'].'" target="_blank" rel="noopener"><img title="More species information including images and audio clips available via eBird" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a></h5></p>';

		$metadata['display.summary'][0]['value'] .= '<h5>Status in KAUST</h5>
		<p>';

		foreach($metadata['kaust.presence.status'] as $status)
		{
			$metadata['display.summary'][0]['value'] .= '<a title="'.$statuses[$status['value']].', click to see all species with this status in KAUST" href="/discover?view=grid&rpp=12&filtertype=kaustStatus&amp;filter_relational_operator=equals&amp;filter='.$status['value'].'" >'.$status['value'].'</a><br>';
		}

		$metadata['display.summary'][0]['value'] .= '</p><h5>Abundance in KAUST</h5>
		<p><a title="Click to see all species with this level of abundance in KAUST" href="/discover?view=grid&rpp=12&filtertype=abundance&amp;filter_relational_operator=equals&amp;filter='.$metadata['kaust.presence.abundance'][0]['value'].'" >'.$metadata['kaust.presence.abundance'][0]['value'].'</a></p>';

		$metadata['display.summary'][0]['value'] .= '<h5 style="text-align: justify;">Places seen in KAUST</h5>';

		$locations = array_unique(getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dwc.location.locality' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL)", array('value'), 'arrayOfValues'));

		unset($metadata['dwc.location.locality']);

		foreach($locations as $location)
		{
			//add location to species summary metadata
			$metadata['dwc.location.locality'][]['value'] = $location;

			$locationHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.title' AND `value` LIKE '$location' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Location' AND `deleted` IS NULL))", array('value'), 'singleValue');

			if(!empty($locationHandle))
			{
				$locationEbirdID = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.location.id' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `value` LIKE '$locationHandle' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Location' AND `deleted` IS NULL))", array('value'), 'singleValue');

				$locationHandle = str_replace('http://hdl.handle.net/', '/handle/',$locationHandle);

				$metadata['display.summary'][0]['value'] .= '<a title="Click to see more information about this location" href="'.$locationHandle.'" >'.$location.'</a>';

				if(!empty($locationEbirdID))
				{
					$metadata['display.summary'][0]['value'] .= '<a href="https://ebird.org/hotspot/'.$locationEbirdID.'" target="_blank" rel="noopener"><img title="eBird hotspot page for this location" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a>';
				}
			}
			else
			{
				$metadata['display.summary'][0]['value'] .= '<a title="Click to see observations from this location" href="/discover?view=grid&rpp=12&filtertype=location&amp;filter_relational_operator=equals&amp;filter='.$location.'" >'.$location.'</a>';
			}
			$metadata['display.summary'][0]['value'] .= '<br>';
		}

		//Insert link to related images search?
		$images = count(array_unique(getValues($irts, "SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Image' AND `deleted` IS NULL)", array('idInSource'), 'arrayOfValues')));

		if($images !== 0)
		{
			$metadata['display.summary'][0]['value'] .= '<h5><a title="Click to search images taken of this species" href="/discover?view=grid&rpp=12&filtertype_0=type&filter_0=Image&filter_relational_operator_0=equals&filtertype=species&filter_relational_operator=equals&filter='.$metadata['dwc.taxon.vernacularName'][0]['value'].'" >Search pictures of '.$metadata['dwc.taxon.vernacularName'][0]['value'].' in KAUST</a></h5>';
		}

		$metadata['display.summary'][0]['value'] .= '</td><td style="vertical-align: top; width: 50%;"><h5>Example Image</h5>';

		if(isset($metadata['ebird.media.id']))
		{
			$metadata['display.summary'][0]['value'] .= '<iframe height="320" src="https://macaulaylibrary.org/asset/'.$metadata['ebird.media.id'][0]['value'].'/embed/320" frameborder="0" allowfullscreen style="width:320px;"></iframe>';
			
			$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($idInSource, $token);

			//If no existing files, post example thumbnail
			if(is_string($bitstreams)&&$bitstreams==='[]')
			{
				$response = postBitstreamToDSpaceRESTAPI($idInSource, "https://download.ams.birds.cornell.edu/api/v1/asset/".$metadata['ebird.media.id'][0]['value']."/320", $metadata['dwc.taxon.vernacularName'][0]['value'].'-ML'.$metadata['ebird.media.id'][0]['value'].'.jpg', $metadata['dwc.taxon.vernacularName'][0]['value'].' Example Image from the Macaulay Library ML'.$metadata['ebird.media.id'][0]['value'], 'THUMBNAIL', $token);

				print_r($response);
			}						
		}
		else
		{
			$metadata['display.summary'][0]['value'] .= '<img title="Image from '.$metadata['dc.contributor.photographer'][0]['value'].'" class="object-fit" src="'.$bitstreamURL.'" alt="Thumbnail" />';

			if(isset($metadata['dc.contributor.photographer']))
			{
				$metadata['display.summary'][0]['value'] .= '<br><p>Image from '.$metadata['dc.contributor.photographer'][0]['value'].'</p>';
			}
		}

		$metadata['display.summary'][0]['value'] .= '</td>
		</tr>
		</tbody>
		</table>';

		$metadata['display.summary'][0]['value'] .= '<h5 style="text-align: justify;">Seasonality in KAUST (number of years since KAUST\'s founding when this species was observed in a given month):</h5>
			<table style="table-layout: fixed; width: 100%; margin-left: auto; margin-right: auto;" border="1">
			<tbody>
			<tr style="background-color: grey;">';

		//add years to metadata (currently indexing allows only 1 year per record
		//unset($metadata['kaust.presence.year']);

		/* $years = array_unique(getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'kaust.presence.year' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL) AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Observation Record' AND `deleted` IS NULL)", array('value'), 'arrayOfValues'));

		foreach($years as $year)
		{
			$metadata['kaust.presence.year'][]['value'] = $year;
		} */

		foreach($months as $shortMonth => $month)
		{
			$metadata['display.summary'][0]['value'] .= '<th style="text-align: center; border: 1px solid black">'.$shortMonth.'</th>';
		}

		$metadata['display.summary'][0]['value'] .= '</tr><tr>';

		unset($metadata['kaust.presence.month']);

		foreach($months as $shortMonth => $month)
		{
			$count = count(array_unique(getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'kaust.presence.year' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL) AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'kaust.presence.month' AND (`value` LIKE '$month' OR `value` LIKE '$shortMonth') AND `deleted` IS NULL) AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Observation Record' AND `deleted` IS NULL)", array('value'), 'arrayOfValues')));

			$monthLink = '/discover?field=monthPresent&filtertype_0=type&filtertype_1=species&filter_0=Observation+Record&filter_relational_operator_1=equals&filter_1='.$metadata['dwc.taxon.vernacularName'][0]['value'].'&filter_relational_operator_0=equals&filtertype=monthPresent&filter_relational_operator=equals&filter='.$month;

			if($count === 0)
			{
				$metadata['display.summary'][0]['value'] .= '<th style="text-align: center; border: 1px solid black">0</th>';
			}
			else
			{
				$metadata['kaust.presence.month'][]['value'] = $month;

				if($count < 4)
				{
					$color = 'yellow';
				}
				elseif($count < 8)
				{
					$color = 'orange';
				}
				else
				{
					$color = 'red';
				}
				$metadata['display.summary'][0]['value'] .= '<th style="background-color: '.$color.'; text-align: center; border: 1px solid black"><a title="Click to see all observation records for '.$metadata['dwc.taxon.vernacularName'][0]['value'].'s in '.$month.'" href="'.$monthLink.'">'.$count.'</a></th>';
			}
		}
		$metadata['display.summary'][0]['value'] .= '</tr>
			</tbody>
			</table>';
			
		//echo $metadata['display.summary'][0]['value'].PHP_EOL;	
						
		return $metadata['display.summary'][0]['value'];
	}