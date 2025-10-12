<?php	
	//Define function to process eBird JSON metadata for a single observation
	function createImageSummary($metadata)
	{
		global $irts;

		$species = $metadata['ebird.species.code'];

		$speciesSummaryHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '$species' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Species Summary' AND `deleted` IS NULL))", array('value'), 'singleValue');

		if(!empty($speciesSummaryHandle))
		{
			$speciesSummaryHandle = str_replace('http://hdl.handle.net/', '/handle/',$speciesSummaryHandle);
		}

		$location = $metadata['dwc.location.locality'];

		$locationHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.title' AND `value` LIKE '$location' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Location' AND `deleted` IS NULL))", array('value'), 'singleValue');

		if(!empty($locationHandle))
		{
			$locationHandle = str_replace('http://hdl.handle.net/', '/handle/',$locationHandle);
		}

		//Start creating display summary
		$metadata['display.summary'] = '';

		if(isset($metadata['ebird.media.id']))
		{
			$metadata['display.summary'] .= '<table style="table-layout: fixed; width: 100%; margin-left: auto; margin-right: auto;">
			<tbody>';

			$metadata['display.summary'] .= '<tr>
			<td style="vertical-align: top; width: 50%;">';
		}

		if(!empty($speciesSummaryHandle))
		{
			$metadata['display.summary'] .= '<p><a title="Summary of information about this species in KAUST" href="'.$speciesSummaryHandle.'" >'.$metadata['dwc.taxon.vernacularName'].'</a> (<em>'.$metadata['dwc.taxon.scientificName'].'</em>)<a href="https://ebird.org/species/'.$metadata['ebird.species.code'].'" target="_blank" rel="noopener"><img title="More species information, including images and audio clips, available via eBird" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a></p>';
		}
		else
		{
			$metadata['display.summary'] = '<p>'.$metadata['dwc.taxon.vernacularName'].' (<em>'.$metadata['dwc.taxon.scientificName'].'</em>)<a href="https://ebird.org/species/'.$metadata['ebird.species.code'].'" target="_blank" rel="noopener"><img title="More species information, including images and audio clips, available via eBird" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a></p>';
		}

		if(isset($metadata['dwc.location.locality']))
		{
			$metadata['display.summary'] .= '<h5>Location</h5>
			<p><a title="Click to see more information about this location" href="'.$locationHandle.'" >'.$metadata['dwc.location.locality'].'</a>';

			if(!empty($metadata['ebird.location.id']))
			{
				$metadata['display.summary'] .= '<a href="https://ebird.org/hotspot/'.$metadata['ebird.location.id'].'" target="_blank" rel="noopener"><img title="eBird hotspot page for this location" class="object-fit" src="/bitstream/handle/10754/644888/ebirdLogo.JPG" alt="Thumbnail" width="42" /></a>';
			}

			$metadata['display.summary'] .= '</p>';
		}

		$metadata['display.summary'] .= '<h5>Photographed By:</h5>
		<p>'.$metadata['dc.contributor.photographer'].'</p>';

		if(isset($metadata['ebird.checklist.id']))
		{
			/*$observationRecordHandle = getValues($irts, "SELECT DISTINCT(`value`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.identifier.uri' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.checklist.id' AND `value` LIKE '".$metadata['ebird.checklist.id']."' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'dc.type' AND `value` LIKE 'Observation Record' AND `deleted` IS NULL AND `idInSource` IN (SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'dspace' AND `field` LIKE 'ebird.species.code' AND `value` LIKE '".$metadata['ebird.species.code']."' AND `deleted` IS NULL)))", array('value'), 'singleValue');

			$observationRecordHandle = str_replace('http://hdl.handle.net/', '/handle/',$observationRecordHandle);

			$metadata['display.summary'] .= '<h5><a href="'.$observationRecordHandle.'">Related observation record</a></h5><br>';*/

			$metadata['display.summary'] .= '<h5>This image is part of <a href="https://ebird.org/view/checklist/'.$metadata['ebird.checklist.id'].'"> eBird checklist '.$metadata['ebird.checklist.id'].'</a></h5>';
		}

		if(isset($metadata['ebird.media.id']))
		{
			$metadata['display.summary'] .= '</td><td style="vertical-align: top; width: 50%;"><iframe height="400" src="https://macaulaylibrary.org/asset/'.$metadata['ebird.media.id'].'/embed/320" frameborder="0" allowfullscreen style="width:320px;"></iframe>';

			$metadata['display.summary'] .= '</td>
			</tr>
			</tbody>
			</table>';
		}
		
		return $metadata['display.summary'];
	}