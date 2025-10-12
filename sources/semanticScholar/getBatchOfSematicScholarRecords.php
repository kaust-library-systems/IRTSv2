<?php
	function getBatchOfSemanticScholarRecords($paperIDsJSON, $commaSeparatedFields)
	{
		$successCode = '200';

		$options = array(
		  CURLOPT_URL => SEMANTIC_SCHOLAR_API.'paper/batch?fields='.$commaSeparatedFields,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $paperIDsJSON,
		  CURLOPT_HTTPHEADER => array(
			"x-api-key: ".SEMANTIC_SCHOLAR_API_KEY,
			"Accept: application/json",
			"Cache-Control: no-cache",
            "User-Agent: IRTS",
			"Content-Type: application/json",
			"Connection: keep-alive"
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		return $response;
	}