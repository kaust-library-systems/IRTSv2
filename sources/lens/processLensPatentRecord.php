<?php
	//Define function to process a patent record retrieved from the Lens.org API and return standard metadata output
	function processLensPatentRecord($input)
	{
		global $irts, $errors, $report;
		
		$source = 'lens';
		
		$output = array();

		//print_r($input);
		
		foreach($input as $field => $value)
		{
			$fieldParts = array();
			
			$output = iterateOverLensFields($source, $output, $fieldParts, $field, $value);
		}
		
		return $output;
	}
