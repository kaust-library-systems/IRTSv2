<?php
	function dspaceGetEpersonsOrGroups($type, $page = 0, $size = 20)
	{
		// Define the success code for the request
		$successCode = '200';

		// Set up the cURL options for the request
		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'eperson/'.$type.'?page='.$page.'&size='.$size,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"Cache-Control: no-cache",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken'],
			'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken']
		  )
		);

		// Make the cURL request and get the response
		$response = makeCurlRequest($options, $successCode);

		// Set the CSRF token from the response headers
		dspaceSetToken($response['headers']);

		return $response;
	}