<?php
	function dspaceMapCollections($itemUUID, $newCollections)
	{
		$responses = [];
        $status = 'success';
        $successCode = '204';
        
        foreach($newCollections as $newCollection)
        {
            $collectionURI = REPOSITORY_API_URL.'core/collections/'.$newCollection;
            
            $options = array(
                CURLOPT_URL => REPOSITORY_API_URL.'core/items/'.$itemUUID.'/mappedCollections',
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $collectionURI,
                CURLOPT_HTTPHEADER => array(
                        "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
                        "Accept: application/json",
                        "Cache-Control: no-cache",
                        "User-Agent: IRTS",
                        "Content-Type: text/uri-list",
                        $_SESSION['dspaceBearerHeader'],
                        "Host: ".REPOSITORY_BASE_URL,
                        "Content-Length: ".strlen($collectionURI),
                        "Connection: keep-alive",
                        "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
                    )
                );

            $response = makeCurlRequest($options, $successCode);

            if($response['status'] != 'success')
            {
                // may need to reauthenticate and retry
                if($response['responseCode'] == '401')
                {
                    //Get initial CSRF token and set in session
                    $response = dspaceGetStatus();
                            
                    //Log in
                    $response = dspaceLogin();

                    $response = makeCurlRequest($options, $successCode);
                }

                //if still failed, mark failure for full batch of mapping requests
                if($response['status'] != 'success')
                {
                    $status = 'failed';
                }
            }

            dspaceSetToken($response['headers']);

            $responses[] = $response;
        }

		return array('status' => $status, 'responses' => $responses);
	}