<?php
	//Define functions to search Pure
	
	function searchPure($server, $endpoint, $searchJSON)
	{
		$successHeader = 'HTTP/1.1 200 200';
		$successResponsePortionNeeded = 'response';
		
		if($server === 'staging')
		{
			$url = PURE_STAGING_API_URL;
			$apikey = PURE_STAGING_API_KEY;
		}
		elseif($server === 'production')
		{
			$url = PURE_API_URL;
			$apikey = PURE_API_KEY;
		}

		$options = array(
		  CURLOPT_URL => $url.$endpoint.'/search',
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => "$searchJSON",
		  CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Cache-Control: no-cache",
			"Content-Type: application/json",
			"api-key: ".$apikey
		  )
		);
		
		$response = makeCurlRequest($options, $successHeader, $successResponsePortionNeeded);

		return $response;
	}