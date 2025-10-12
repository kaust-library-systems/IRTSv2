<?php
	function dspaceMoveBitstream($bitstreamUUID, $newBundleUUID)
	{
        $successCode = '200';
        
        $bundleURI = REPOSITORY_API_URL.'core/bundles/'.$newBundleUUID;
        
        $options = array(
            CURLOPT_URL => REPOSITORY_API_URL.'core/bitstreams/'.$bitstreamUUID.'/bundle',
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $bundleURI,
            CURLOPT_HTTPHEADER => array(
                    "X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
                    "Accept: application/json",
                    "Cache-Control: no-cache",
                    "User-Agent: IRTS",
                    "Content-Type: text/uri-list",
                    $_SESSION['dspaceBearerHeader'],
                    "Host: ".REPOSITORY_BASE_URL,
                    "Content-Length: ".strlen($bundleURI),
                    "Connection: keep-alive",
                    "Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
                )
            );

        $response = makeCurlRequest($options, $successCode);

        dspaceSetToken($response['headers']);

        $responses[] = $response;

		return $response;
	}