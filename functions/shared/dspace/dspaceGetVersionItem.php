<?php
    //This function gets a version item by version ID
    function dspaceGetVersionItem($versionID)
    {
        $successCode = '200';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'versioning/versions/'.$versionID.'/item/',
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

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
    }