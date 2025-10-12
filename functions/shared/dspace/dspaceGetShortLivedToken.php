<?php
	function dspaceGetShortLivedToken()
	{
		$successCode = '200';

		$options = array(
			CURLOPT_URL => REPOSITORY_API_URL."authn/shortlivedtokens",
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken'],
				"User-Agent: IRTS",
				$_SESSION['dspaceBearerHeader'],
				"Host: ".REPOSITORY_BASE_URL,
				"Connection: keep-alive",
				'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken']
			)
			);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}