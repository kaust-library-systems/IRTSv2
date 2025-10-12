<?php
	//userType can be either 'eperson' or 'group', userUUID will be the group or eperson UUID
	function dspaceCreateResourcePolicy($resourceUUID, $userType, $userUUID, $policy)
	{
		$successCode = '201';

		$options = array(
		  CURLOPT_URL => REPOSITORY_API_URL.'/authz/resourcepolicies?resource='.$resourceUUID.'&'.$userType.'='.$userUUID,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_POSTFIELDS => $policy,
		  CURLOPT_HTTPHEADER => array(
			"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: application/json",
			$_SESSION['dspaceBearerHeader'],
			"Host: ".REPOSITORY_BASE_URL,
			"Content-Length: ".strlen($policy),
			"Connection: keep-alive",
			"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}