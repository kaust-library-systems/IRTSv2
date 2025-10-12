<?php	
	echo 'This item, with IRTS ID '.$idInIRTS. ', will be marked as depositable.<hr><br>';

	//If item has no acknowledgement, set kaust.acknowledgement.type value and move to dataAvailability step
	if($step === 'acknowledgementsPlus')
	{
		if(empty($record['dc.description.sponsorship'][0]))
		{
			$record['kaust.acknowledgement.type'][0] = 'No acknowledgement';
			
			$step = 'dataAvailability';
		}
	}
	
	//If item has no data availability statement, skip to rights step
	if($step === 'dataAvailabilityPlus')
	{
		if(empty($record['dc.description.dataAvailability'][0]))
		{
			$step = 'rights';
		}
	}

	//If item has acknowledgement, but not affiliated authors, skip to the review step
	if($step === 'dataAvailability')
	{
		$affiliated = FALSE;
		
		foreach($record['dc.contributor.affiliation'] as $affiliation)
		{
			if(institutionNameInString($affiliation))
			{
				$affiliated = TRUE;
			}
		}
		
		$acknowledged = FALSE;
		
		foreach($record['dc.description.sponsorship'] as $acknowledgement)
		{
			if(institutionNameInString($acknowledgement))
			{
				$acknowledged = TRUE;
			}
		}
		
		if(!$affiliated && $acknowledged)
		{
			$step = 'review';
		}
	}

	if($step == 'relations') //expand relations to allow reassignment of relation type by dropdown
	{
		$relationCount = 0;
		foreach($record as $field=>$values)
		{
			if(strpos($field, 'dc.relation.') !== FALSE && $field !== 'dc.relation.url')
			{
				foreach($values as $value)
				{
					$record['dc.relationType'][$relationCount] = str_replace('dc.relation.','',$field);
					
					$record['dc.relationType']['dc.relatedIdentifier'][$relationCount][] = $value;
					
					$relationCount++;
				}
				unset($record[$field]);
			}
		}
	}
	elseif($step === 'rights')
	{
		include_once "snippets/forMetadataEntry/rights.php";
	}
	elseif($step === 'acknowledgementsPlus')
	{
		$record = prepareAcknowledgements($record);
	}
	elseif($step === 'chapters')
	{
		$record = prepareChapters($record);
	}
	elseif($step === 'review')
	{
		if(isset($record['irts.contributor.type']))
		{
			if($record['irts.contributor.type'][0] === 'Editors')
			{
				$record['dc.contributor.editor'] = $record['dc.contributor.author'];
				unset($record['dc.contributor.author']);
			}
			elseif($record['irts.contributor.type'][0] === 'Authors')
			{
				unset($record['dc.contributor.editor']);
			}
			unset($record['irts.contributor.type']);
			unset($record['dc.contributor.affiliation']);
		}
	}

	echo displayForm($selections, $record, $template, $step, $page, $idInIRTS);
