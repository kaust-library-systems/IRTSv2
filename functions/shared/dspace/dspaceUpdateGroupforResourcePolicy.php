
<?php
	function dspaceUpdateGroupforResourcePolicy($resourcepolicyID, $GroupUUID)
	{
		$successCode = '200';
		$GroupURl = REPOSITORY_API_URL.'/eperson/groups/'.$GroupUUID;

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'/authz/resourcepolicies/'.$resourcepolicyID.'/group',
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_POSTFIELDS => $GroupURl,
		  CURLOPT_HTTPHEADER => array(
			"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: text/uri-list",
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Content-Length: ".strlen($GroupURl),
			"Connection: keep-alive",
			"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}