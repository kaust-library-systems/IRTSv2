<?php
	//The Sherpa Romeo API v2 documentation is at: https://v2.sherpa.ac.uk/romeo/api.html
	
	function querySherpaRomeo($type, $filter, $offset = NULL)
	{
		$url = SHERPA_ROMEO_API_URL."item-type=$type&api-key=".SHERPA_ROMEO_API_KEY.'&format=Json';
		
		if(!empty($filter))
		{
			$url .= '&filter=[["'.$filter['field'].'","'.$filter['operator'].'","'.urlencode($filter['value']).'"]]';
		}
		
		if(!empty($offset))
		{
			$url .= "&offset=$offset";
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		
		if (curl_errno($ch)) {
			throw new Exception("cURL error: " . curl_error($ch));
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode !== 200) {
			throw new Exception("HTTP error: $httpCode");
		}
		
		curl_close($ch);
		$json = json_decode($response, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception("Failed to decode JSON response: " . json_last_error_msg());
		}
		
		return $json;
		
	}
