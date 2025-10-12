<?php

/*

**** This function is responsible for retrieving the Unpaywall result for a DOI.

** Parameters :
	$doi : 
	
*/

//-----------------------------------------------------------------------------------------------------------

function queryUnpaywall($doi){

	$successCode = '200';

	$options = array(
	  CURLOPT_URL => UNPAYWALL_API_URL.$doi.'?email='.IR_EMAIL,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
		"Accept: */*",
		"accept-encoding: gzip, deflate",
		"cache-control: no-cache"
		),
	);

	$response = makeCurlRequest($options, $successCode);
	return $response;
}
