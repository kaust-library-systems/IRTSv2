<?php
	function dspaceDeleteResourcePolicy($resourcepolicyID)
	{
		$successCode = '204';

		$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'/authz/resourcepolicies/'.$resourcepolicyID,
				CURLOPT_CUSTOMREQUEST => 'DELETE',
				CURLOPT_HTTPHEADER => array(
				"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
				"Accept: application/json",
				"Cache-Control: no-cache",
				"User-Agent: IRTS",
				"Content-Type: application/json",
				$_SESSION['dspaceBearerHeader'],
				"Host: ".REPOSITORY_BASE_URL,
				"Connection: keep-alive",
				"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
			)
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}