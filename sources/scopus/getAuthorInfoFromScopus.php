<?php
/*

**** This function responsible of returning the author name for Scopus by id

** Parameters :
	$scopusID = the Scopus Author ID to query

*/

//--------------------------------------------------------------------------------------------

function getAuthorInfoFromScopus($scopusID){

	$successHeader = 'HTTP/1.1 200 OK';
	$successResponsePortionNeeded = 'response';

	$options = array(
		  CURLOPT_URL => ELSEVIER_API_URL."author/author_id/".$scopusID.'?apiKey='.ELSEVIER_API_KEY,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Cache-Control: no-cache",
			"Accept: application/json"
		  )
		);

	$response = makeCurlRequest($options, $successHeader, $successResponsePortionNeeded);

	return $response;
}
