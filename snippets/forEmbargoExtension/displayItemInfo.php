<?php
	$handle = $item['handle'];
		
	$message .= displayRepositoryLinks($itemID, $handle);

	$receivedEmailID = $item['metadata']['kaust.embargo.extensionRequested'][0]['value'];
	
	$title = $item['metadata']['dc.title'][0]['value'];
	
	$type = $item['metadata']['dc.type'][0]['value'];

	$message .= 'Title: '.$title.'
				<br>
				Type: '.$type.'
				<br>';

	$authors = [];

	if(isset($item['metadata']['dc.contributor.author']))
	{
		foreach($item['metadata']['dc.contributor.author'] as $author)
		{
			$authors[] = $author['value'];
		}
	}

	if(!empty($authors))
	{
		$message .= 'Authors: '.implode('; ', $authors).'
		<br>';
	}	
	
	$advisors = [];

	if(isset($item['metadata']['dc.contributor.advisor']))
	{
		foreach($item['metadata']['dc.contributor.advisor'] as $advisor)
		{
			$advisors[] = $advisor['value'];
		}
	}

	if(!empty($advisors))
	{
		$message .= 'Advisor(s): '.implode('; ', $advisors).'
		<br>';
	}	

	if(isset($item['metadata']['dc.rights.embargodate']))
	{
		$embargoEndDate = $item['metadata']['dc.rights.embargodate'][0]['value'];
		
		$message .= 'Embargo: '.$embargoEndDate.'
			<br>';
	}
	else
	{
		$message .= 'Embargo: <div class="col-sm-12 alert-danger border border-dark rounded">No existing embargo on the item. Approving the request will add a new embargo (instead of extending an existing embargo).</div>
			<br>';

		$embargoEndDate = TODAY;
	}
	
?>