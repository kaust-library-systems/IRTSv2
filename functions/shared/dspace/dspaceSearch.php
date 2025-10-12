<?php
	function dspaceSearch($query, $anonymous = FALSE)
	{
		$successCode = '200';

		if(isset($_SESSION['dspaceBearerHeader'])&&!$anonymous)
		{
			$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'discover/search/objects?'.$query,
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
		}
		else
		{
			$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'discover/search/objects?'.$query,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Connection: keep-alive",
				"Cache-Control: no-cache",
				"Content-Type: application/json"
				)
			);
		}

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}