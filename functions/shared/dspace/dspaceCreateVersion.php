<?php
	function dspaceCreateVersion($itemUUID, $reasonForVersioning)
	{
		$successCode = '201';

    $itemURI = REPOSITORY_API_URL.'core/items/'.$itemUUID;

    $options = array(
        CURLOPT_URL => REPOSITORY_API_URL.'versioning/versions?summary='.urlencode($reasonForVersioning),
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $itemURI,
        CURLOPT_HTTPHEADER => array(
          "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
          "Accept: application/json",
          "Cache-Control: no-cache",
          "User-Agent: IRTS",
          "Content-Type: text/uri-list",
          $_SESSION['dspaceBearerHeader'],
          "Host: ".REPOSITORY_BASE_URL,
          "Content-Length: ".strlen($itemURI),
          "Connection: keep-alive",
          "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
        )
      );

    $response = makeCurlRequest($options, $successCode);

    //$response['options'] = $options;

    dspaceSetToken($response['headers']);
        
		return $response;
	}