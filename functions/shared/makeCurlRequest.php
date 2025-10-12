<?php
	//Define base function with default options for making Curl requests
	
	/*
	** Parameters :
		$customOptions : the array of curl options specific to the request .
		$successCode : the expected response code to indicate that the request was successful .
	*/
	
	function makeCurlRequest($customOptions, $successCode = '200')
	{
		set_time_limit(60); // Set the script timeout to 60 seconds
		
		$curl = curl_init();

		$headers = [];
		
		$defaultOptions = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$headers)
			{
				$len = strlen($header);
				
				if (trim($header) !== "") { // Avoid adding empty lines
					$headers[] = $header;
				}

				return $len;
			},
		CURLOPT_HTTPHEADER => array("Expect:") // Remove the "Expect: 100-continue" HTTP header (automatically added when POSTing more than 1MB of data)
		);
		
		$options = $customOptions + $defaultOptions;
		
		//print_r($options);
		
		curl_setopt_array($curl, $options);
		
		$body = curl_exec($curl);
		$error = curl_error($curl);

		$responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

		curl_close($curl);

		if($error)
		{
			$error = "cURL Error #:" . $error;
		}

		if($responseCode == $successCode)
		{
			$status = 'success';
		}
		else
		{
			$status = 'failed';

			$error = "Response code received (". $responseCode.") does not match expected response code (".$successCode.")";
		}		

		return array('status'=>$status,'error'=>$error,'body'=>$body,'headers'=>$headers,'responseCode'=>$responseCode);
	}
