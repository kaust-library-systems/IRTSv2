<?php
	function dspaceLogin()
	{
		$successCode = '200';

		$options = array(
			CURLOPT_URL => REPOSITORY_API_URL."authn/login",
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => 'user='.REPOSITORY_USER.'&password='.REPOSITORY_PW,
			CURLOPT_HTTPHEADER => array(
				'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken'],
				"User-Agent: IRTS",
				"Host: ".REPOSITORY_BASE_URL,
				"Connection: keep-alive",
				'Content-Type: application/x-www-form-urlencoded',
				'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken']
			)
			);

		$response = makeCurlRequest($options, $successCode);

		//print_r($response);

		dspaceSetToken($response['headers']);
		
		if($response['status'] == 'success')
		{
			dspaceSetBearerHeader($response['headers']);
		}

		return $response;
	}