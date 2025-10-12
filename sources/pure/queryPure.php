<?php
	//Define functions to retrieve records
	
	function queryPure($endpoint, $type, $offset = 0, $size = 1, $order = 'modified', $queryJSON = NULL)
	{
		$successCode = '200';

		$options = array(
			  CURLOPT_URL => PURE_API_URL.$endpoint.'?offset='.$offset.'&size='.$size.'&order='.$order,
			  CURLOPT_CUSTOMREQUEST => $type,
			  CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Cache-Control: no-cache",
				"Content-Type: application/json",
				"api-key: ".PURE_API_KEY
			  )
			);
		
		if($type === 'POST')
		{
			$options[CURLOPT_POSTFIELDS] = "$queryJSON";
		}
		
		$response = makeCurlRequest($options, $successCode);

		return $response;
	}