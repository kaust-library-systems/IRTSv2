<?php	
	//Define function to process eBird JSON metadata for a single observation
	function createObservationSummary($metadata)
	{
		global $irts;

		$species = $metadata['ebird.species.code'];

		$location = $metadata['dwc.location.locality'];

		$speciesSummaryHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Species Summary' AND `deleted` IS NULL))", array('value'), 'singleValue');

		if(!empty($speciesSummaryHandle))
		{
			$speciesSummaryHandle = str_replace('http://hdl.handle.net/', '/handle/',$speciesSummaryHandle);
		}

		$locationHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.title' AND `value` LIKE '$location' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Location' AND `deleted` IS NULL))", array('value'), 'singleValue');

		if(!empty($locationHandle))
		{
			$locationHandle = str_replace('http://hdl.handle.net/', '/handle/',$locationHandle);
		}

		if(!empty($speciesSummaryHandle))
		{
			$metadata['display.summary'] = '<p><a title="Summary of information about this species in KAUST" href="'.$speciesSummaryHandle.'" >'.$metadata['dwc.taxon.vernacularName'].'</a> (<em>'.$metadata['dwc.taxon.scientificName'].'</em>)<a href="https://ebird.org/species/'.$metadata['ebird.species.code'].'" target="_blank" rel="noopener"><img title="More species information, including images and audio clips, available via eBird" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a></p>';
		}
		else
		{
			$metadata['display.summary'] = '<p>'.$metadata['dwc.taxon.vernacularName'].' (<em>'.$metadata['dwc.taxon.scientificName'].'</em>)<a href="https://ebird.org/species/'.$metadata['ebird.species.code'].'" target="_blank" rel="noopener"><img title="More species information, including images and audio clips, available via eBird" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a></p>';
		}

		if(isset($metadata['dwc.occurrence.individualCount'])&&$metadata['dwc.occurrence.individualCount']!=='X')
		{
			$metadata['display.summary'] .= '<h5>Number of individual birds seen</h5>
			<p>'.$metadata['dwc.occurrence.individualCount'].'</p>';
		}

		$metadata['display.summary'] .= '<h5>Location</h5>
		<p><a href="'.$locationHandle.'" >'.$metadata['dwc.location.locality'].'</a>';

		if(!empty($metadata['ebird.location.id']))
		{
			$metadata['display.summary'] .= '<a href="https://ebird.org/hotspot/'.$metadata['ebird.location.id'].'" target="_blank" rel="noopener"><img title="eBird hotspot page for this location" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a>';
		}

		$metadata['display.summary'] .= '</p>';

		if(!isset($metadata['dc.date.observed'])&&isset($metadata['dc.date.issued']))
		{
			$metadata['dc.date.observed'] = $metadata['dc.date.issued'];
		}

		if(isset($metadata['dc.date.observed']))
		{
			$metadata['display.summary'] .= '<h5 style="text-align: justify;">Observation Date</h5>
			<p>'.$metadata['dc.date.observed'].'</p>';
		}

		if(isset($metadata['ebird.observation.time']))
		{
			$metadata['display.summary'] .= '<h5>Observation Time</h5>
			<p>'.$metadata['ebird.observation.time'].'</p>';
		}

		$metadata['display.summary'] .= '<h5>Observed By</h5>
		<p>'.$metadata['dwc.occurrence.recordedBy'].'</p>';

		if(isset($metadata['dc.description.note']))
		{
			$metadata['display.summary'] .= '<h5>Notes</h5>
			<p>'.$metadata['dc.description.note'].'</p>';
		}

		if(isset($metadata['ebird.checklist.id']))
		{
			$metadata['display.summary'] .= '<h5>This observation was made as part of <a href="https://ebird.org/view/checklist/'.$metadata['ebird.checklist.id'].'"> eBird checklist '.$metadata['ebird.checklist.id'].'</a></h5>';
		}
		return $metadata['display.summary'];
	}