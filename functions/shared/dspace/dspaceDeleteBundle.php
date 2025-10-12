<?php
	function dspaceDeleteBundle($bundleUUID)
	{
		$successCode = '204';

		$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'core/bundles/'.$bundleUUID,
				CURLOPT_CUSTOMREQUEST => 'DELETE',
				CURLOPT_HTTPHEADER => array(
				"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
				"Cache-Control: no-cache",
				"User-Agent: IRTS",
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