<?php
	function dspaceGetBitstreamsContent($id, $shortLivedToken = '')
	{
		$successCode = '200';

		if(isset($_SESSION['dspaceBearerHeader']))
		{
			$options = array(
			CURLOPT_URL => REPOSITORY_API_URL.'core/bitstreams/'.$id.'/content',
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Cache-Control: no-cache",
				"Content-Type: application/json",
				$_SESSION['dspaceBearerHeader'],
				'Cookie: DSPACE-XSRF-COOKIE='.$_SESSION['dspaceCsrfToken'],
				'X-XSRF-TOKEN: '.$_SESSION['dspaceCsrfToken']
			)
			);
		}
		elseif(!empty($shortLivedToken))
		{
			$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'core/bitstreams/'.$id.'/content?authentication-token='.$shortLivedToken,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Cache-Control: no-cache",
					"Content-Type: application/json"
				)
				);
		}
		else
		{
			$options = array(
				CURLOPT_URL => REPOSITORY_API_URL.'core/bitstreams/'.$id.'/content',
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"Cache-Control: no-cache",
					"Content-Type: application/json"
				)
				);
		}

		$response = makeCurlRequest($options, $successCode);

		dspaceSetToken($response['headers']);

		return $response;
	}