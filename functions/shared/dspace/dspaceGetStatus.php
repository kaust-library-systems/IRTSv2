<?php
	function dspaceGetStatus()
	{
		//$successCode = '200';
		$successCode = '403';
		
		/*$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL."authn/status",
		  CURLOPT_CUSTOMREQUEST => "GET"
		);*/
		
		$options = array(
			CURLOPT_URL => REPOSITORY_API_URL."authn/login",
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => 'user='.REPOSITORY_USER.'&password='.REPOSITORY_PW,
			CURLOPT_HTTPHEADER => array(
				
				"User-Agent: IRTS",
				"Host: ".REPOSITORY_BASE_URL,
				"Connection: keep-alive",
				'Content-Type: application/x-www-form-urlencoded'
				
			)
			);
		
		$response = makeCurlRequest($options, $successCode);

		//print_r($response).PHP_EOL;

		dspaceSetToken($response['headers']);

		return $response;
	}