<?php
	function dspaceLogout()
	{
		$successCode = '204';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL."authn/logout",
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_HTTPHEADER => array(
			'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken'],
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Connection: keep-alive",
			'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken']			
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		if($response['status'] == 'success')
		{
			unset($_SESSION['dspaceBearerHeader']);
		}

		dspaceSetToken($response['headers']);

		return $response;
	}