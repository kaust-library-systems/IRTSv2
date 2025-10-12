<?php
	//This function submits a workspace item to the workflow
    function dspaceSubmitWorkspaceItemToWorkflow($workspaceItemID)
    {
        $successCode = '201';

        $workspaceItemURI = REPOSITORY_API_URL.'submission/workspaceitems/'.$workspaceItemID;

        $options = array(
            CURLOPT_URL => REPOSITORY_API_URL.'workflow/workflowitems',
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $workspaceItemURI,
            CURLOPT_HTTPHEADER => array(
              "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
              "Accept: application/json",
              "Cache-Control: no-cache",
              "User-Agent: IRTS",
              "Content-Type: text/uri-list",
              $_SESSION['dspaceBearerHeader'],
              "Host: ".REPOSITORY_BASE_URL,
              "Content-Length: ".strlen($workspaceItemURI),
              "Connection: keep-alive",
              "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
            )
          );

        $response = makeCurlRequest($options, $successCode);

        dspaceSetToken($response['headers']);
        
		return $response;
    }