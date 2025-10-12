<?php
	//check if the citation is already in the database
	$citation = trim(getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.identifier.citation'), array('value'), 'singleValue'));
	
	if(empty($citation)&&!empty($doi))
	{
		$response = getCitationByDOI($doi);

		if($response['status'] === 'success')
		{
			$citation = trim($response['body']);
		}
	}
	
	if(empty($citation))
	{
		$citation = 'Type: '.getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.type'), array('value'), 'singleValue').'
		Title: '.getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.title'), array('value'), 'singleValue').'
		Authors: '.implode('; ', getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.contributor.author'), array('value'))).'
		Publication Date: '.getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.date.issued'), array('value'), 'singleValue');

		$journal = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.identifier.journal'), array('value'), 'singleValue');

		if(!empty($journal))
		{
			$citation = $citation.'
			Journal: '.$journal;
		}

		$conference = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'dc.conference.name'), array('value'), 'singleValue');

		if(!empty($conference))
		{
			$citation = $citation.'
			Conference: '.$conference;
		}
	}

	if(!empty($doi))
	{
		$citation = $citation.'
		DOI: '.$doi;
	}
	else
	{
		$citation = $citation.' 
		Handle: '.$handle;
	}
?>