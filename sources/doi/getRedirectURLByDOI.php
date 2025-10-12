<?php
	//Define function to get the URL that a DOI redirects to
	function getRedirectURLByDOI($doi)
	{
		$options = array(
		  CURLOPT_URL => DOI_BASE_URL.$doi,
		  CURLOPT_HEADER => TRUE,
		  CURLOPT_FOLLOWLOCATION => FALSE
		);

		$response = makeCurlRequest($options, '302');

		$locationFound = FALSE;

		$location = '';
		
		//print_r($response);
		if($response['status'] == 'success')
		{
			foreach($response['headers'] as $header)
			{
				$headerParts = explode(': ', $header);

				if($headerParts[0] == 'location')
				{
					$location = trim($headerParts[1]);

					$locationFound = TRUE;

					break;
				}
			}
		}

		if(!$locationFound)
		{
			//print_r($response);
		}
		
		//echo $location;

		return $location;
	}