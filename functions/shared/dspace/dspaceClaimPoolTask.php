<?php
	  //This function claims a pool task
    function dspaceClaimPoolTask($poolTaskID)
    {
      $successCode = '201';

      $poolTaskURI = REPOSITORY_API_URL.'workflow/pooltasks/'.$poolTaskID;

      $options = array(
          CURLOPT_URL => REPOSITORY_API_URL.'workflow/claimedtasks',
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $poolTaskURI,
          CURLOPT_HTTPHEADER => array(
            "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
            "Accept: application/json",
            "Cache-Control: no-cache",
            "User-Agent: IRTS",
            "Content-Type: text/uri-list",
            $_SESSION['dspaceBearerHeader'],
            "Host: ".REPOSITORY_BASE_URL,
            "Content-Length: ".strlen($poolTaskURI),
            "Connection: keep-alive",
            "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
          )
        );

      $response = makeCurlRequest($options, $successCode);

      dspaceSetToken($response['headers']);
        
		  return $response;
    }