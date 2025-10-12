<?php
	//The Semantic Scholar API documentation is at: https://api.semanticscholar.org/
	function retrieveSemanticScholarMetadata($type, $value)
	{
		global $report;
		
		$json = '';
		
		$url = SEMANTIC_SCHOLAR_API."$type/$value";
		
		$report .= '-- '.$url.PHP_EOL;
				
		$json = file_get_contents($url);
		$json = json_decode($json, TRUE);

		return $json;
	}
