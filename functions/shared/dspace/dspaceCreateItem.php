<?php
	function dspaceCreateItem($owningCollectionUUID, $item)
	{
		$successCode = '201';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'core/items?owningCollection='.$owningCollectionUUID,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $item,
		  CURLOPT_HTTPHEADER => array(
			"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Content-Length: ".strlen($item),
			"Connection: keep-alive",
			"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}