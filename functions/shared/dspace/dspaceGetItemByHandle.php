<?php
	function dspaceGetItemByHandle($handle)
	{
		$successCode = '200';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'pid/find?id='.$handle,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Cache-Control: no-cache",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken'],
			'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}