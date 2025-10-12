<?php
	function dspaceCreateCommunity($Community, $parentCommunityUUID = NULL)
	{
		$successCode = '201';
		
		if (is_null($parentCommunityUUID))
		{
			$url = REPOSITORY_API_URL.'core/communities';
		}
		else {
			$url = REPOSITORY_API_URL.'core/communities?parent='.$parentCommunityUUID;
		}
		
		$options = array(
		  CURLOPT_URL => $url,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $Community,
		  CURLOPT_HTTPHEADER => array(
			"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Content-Length: ".strlen($Community),
			"Connection: keep-alive",
			"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}