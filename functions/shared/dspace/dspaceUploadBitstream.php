<?php
	function dspaceUploadBitstream($bundleUUID, $filePath, $fileProperties)
	{
		$successCode = '201';

		$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'core/bundles/'.$bundleUUID.'/bitstreams',
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => array(
					'file' => new CURLFILE($filePath),
					'properties' => $fileProperties
				),
				CURLOPT_HTTPHEADER => array(
				"X-XSRF-TOKEN: ".$_SESSION['dspaceCsrfToken'],
				"Accept: application/json",
				"Cache-Control: no-cache",
				"User-Agent: IRTS",
				"Content-Type: multipart/form-data",
				$_SESSION['dspaceBearerHeader'],
				"Host: ".REPOSITORY_BASE_URL,
				"Connection: keep-alive",
				"Cookie: DSPACE-XSRF-COOKIE=".$_SESSION['dspaceCsrfToken']
			)
		);

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}