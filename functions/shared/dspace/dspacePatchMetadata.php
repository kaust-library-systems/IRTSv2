<?php
	function dspacePatchMetadata($endpoint, $uuid, $patch)
	{
		$successCode = '200';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'core/'.$endpoint.'/'.$uuid,
		  CURLOPT_CUSTOMREQUEST => "PATCH",
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_POSTFIELDS => $patch,
		  CURLOPT_HTTPHEADER => array(
			"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Content-Length: ".strlen($patch),
			"Connection: keep-alive",
			"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		// may need to reauthenticate and retry if this is part of a long process and the token has expired
		if($response['responseCode'] == '401')
		{
			//Get initial CSRF token and set in session
			$response = dspaceGetStatus();
					
			//Log in
			$response = dspaceLogin();

			$response = makeCurlRequest($options, $successCode);
		}

		dspaceSetToken($response['headers']);

		return $response;
	}