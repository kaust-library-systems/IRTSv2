<?php
	function dspaceSearchForEpersonByEmail($email)
	{
		$successCode = '200';

		$options = array(
			CURLOPT_URL => REPOSITORY_API_URL.'eperson/epersons/search/byEmail?email='.$email,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Connection: keep-alive",
				"Cache-Control: no-cache",
				"Content-Type: application/json",
				$_SESSION['dspaceBearerHeader'],
				"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
			)
			);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}