<?php
	function markEmailAsComplete($messageID)
	{
		$successCode = '200';

		$options = array(
		  CURLOPT_URL => POWER_AUTOMATE_SET_EMAIL_CATEGORY_AS_COMPLETE_ENDPOINT,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => '{
			"messageID": "'.$messageID.'"
		  }',
		  CURLOPT_HTTPHEADER => array(
			"Content-Type: application/json"
		  )
		);

		$response = makeCurlRequest($options, $successCode);

		return $response;
	}