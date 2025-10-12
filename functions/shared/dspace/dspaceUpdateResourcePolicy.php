<?php
	function dspaceUpdateResourcePolicy($resourcepolicyID, $patch)
	{
		$successCode = '200';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'/authz/resourcepolicies/'.$resourcepolicyID,
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

		dspaceSetToken($response['headers']);

		return $response;
	}