<?php
	//Define function to convert a DSpace metadata array to a simpler array
	function dspaceMetadataToArray($input)
	{
		$output = array();
		
		foreach($input as $field => $entries)
		{
		  foreach($entries as $entry)
		  {
			if(is_string($entry))
			{
				$output[$field][] = $entry;
			}
			else
			{
				$output[$field][] = $entry['value'];
			}			
		  }
		}
		
		return $output;
	}
