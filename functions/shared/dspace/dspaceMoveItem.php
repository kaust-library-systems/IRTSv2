<?php
	function dspaceMoveItem($itemUUID, $newOwningCollectionUUID)
	{
        $successCode = '200';
        
        $collectionURI = REPOSITORY_API_URL.'core/collections/'.$newOwningCollectionUUID;
        
        $options = array(
            CURLOPT_URL => REPOSITORY_API_URL.'core/items/'.$itemUUID.'/owningCollection',
            CURLOPT_CUSTOMREQUEST => "PUT",
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

        dspaceSetToken($response['headers']);

        $responses[] = $response;

		return $response;
	}