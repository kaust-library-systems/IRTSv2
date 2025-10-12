<?php
	//Define function to process an item retrieved from the WOS Starter API
	function processWosRecord($input)
	{
		global $irts, $errors, $report;
		
		$source = 'wos';
		
		$output = array();

		//print_r($input);
		
		foreach($input as $field => $value)
		{
			//initiate field parts as empty array
			$fieldParts = [];

			//initiate place as 0
			$place = 0;

			$output = iterateOverWosFields($source, $output, $fieldParts, $field, $place, $value);
		}

		//print_r($output);
		
		if($output['wos.sourceTypes'][0]['value'] === 'Proceedings Paper')
		{
			$output['dc.type'][0]['value'] = 'Conference Paper';

			$output['dc.conference.name'][0]['value'] = $output['wos.source.sourceTitle'][0]['value'];
		}
		elseif($output['wos.sourceTypes'][0]['value'] === 'Meeting Abstract')
		{
			$output['dc.type'][0]['value'] = 'Presentation';

			$output['dc.conference.name'][0]['value'] = $output['wos.source.sourceTitle'][0]['value'];
		}
		elseif($output['wos.sourceTypes'][0]['value'] === 'Review')
		{
			$output['dc.type'][0]['value'] = 'Article';

			$output['dc.identifier.journal'][0]['value'] = $output['wos.source.sourceTitle'][0]['value'];
		}
		elseif($output['wos.sourceTypes'][0]['value'] === 'Editorial Material')
		{
			$output['dc.type'][0]['value'] = 'Article';

			$output['dc.identifier.journal'][0]['value'] = $output['wos.source.sourceTitle'][0]['value'];
		}
		elseif($output['wos.sourceTypes'][0]['value'] === 'Letter')
		{
			$output['dc.type'][0]['value'] = 'Article';

			$output['dc.identifier.journal'][0]['value'] = $output['wos.source.sourceTitle'][0]['value'];
		}
		else
		{
			$output['dc.type'][0]['value'] = $output['wos.sourceTypes'][0]['value'];
		}
		
		return $output;
	}
?>