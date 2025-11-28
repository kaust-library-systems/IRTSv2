<?php
	//The arXiv API documentation is at: https://arxiv.org/help/api/index
	function retrieveArxivMetadata($type, $value)
	{
		global $report;
		
		$xml = '';
		
		if($type === 'arxivID')
		{
			$url = ARXIV_API_URL."id:" . $value . "&start=0&max_results=1";
		}
		elseif($type === 'name')
		{
			//LASTNAME,+FIRSTNAME
			$nameParts = explode(', ', $value);
		
			//$value = $nameParts[1].'_'.$nameParts[0];
			$value = $nameParts[0].',+'.$nameParts[1];

			
			//$value = str_replace(' ', '_', $value);

			//$url = ARXIV_API_URL.'au:%22'.$value.'%22&sortBy=submittedDate&sortOrder=descending&start=0&max_results=30';
			$url = ARXIV_API_URL.'au:%22'.$value.'%22&sortBy=submittedDate&sortOrder=descending&start=0&max_results=300';
			
		}
		elseif($type === 'OAI')
		{
			$url = 'http://export.arxiv.org/oai2?verb=GetRecord&identifier=oai:arXiv.org:'.$value.'&metadataPrefix=arXivRaw';
		}
		
		$report .= '-- '.$url.PHP_EOL;

		//echo $url.PHP_EOL;
		
		//use makeCurlRequest function to retrieve the XML
		$options = array(
		  CURLOPT_URL => $url,
		  CURLOPT_CUSTOMREQUEST => "GET"
		);
		
		$response = makeCurlRequest($options);

		//print_r($response);

		//flush output buffer
		//ob_flush();

		if($response['status'] === 'success')
		{
			$xml = simplexml_load_string($response['body']);
			
			if($xml === FALSE)
			{
				$report .= 'Error parsing XML: '.print_r(libxml_get_errors(), TRUE).PHP_EOL;
				return FALSE;
			}
		}
		else
		{
			$report .= 'Error retrieving metadata: '.print_r($response, TRUE).PHP_EOL;
			return FALSE;
		}

		return $xml;
	}
