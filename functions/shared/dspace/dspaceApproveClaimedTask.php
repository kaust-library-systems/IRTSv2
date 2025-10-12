<?php
	  //This function approves a claimed task
    function dspaceApproveClaimedTask($claimedTaskID)
    {
      $successCode = '204';

      $options = array(
          CURLOPT_URL => REPOSITORY_API_URL.'workflow/claimedtasks/'.$claimedTaskID,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => 'submit_approve=true',
          CURLOPT_HTTPHEADER => array(
            "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
            "Accept: application/json",
            "Cache-Control: no-cache",
            "User-Agent: IRTS",
            "Content-Type: application/x-www-form-urlencoded",
            $_SESSION['dspaceBearerHeader'],
            "Host: ".REPOSITORY_BASE_URL,
            "Content-Length: ".strlen('submit_approve=true'),
            "Connection: keep-alive",
            "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
          )
        );

      $response = makeCurlRequest($options, $successCode);

      dspaceSetToken($response['headers']);
        
		  return $response;
    }