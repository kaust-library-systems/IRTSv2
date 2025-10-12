<?php

/*

**** This file responsible for requesting json data from github.

** Parameters :
	$githubURL: stirng.

*/

//--------------------------------------------------------------------------------------------

function queryGithub($githubID) 
{
	$successCode = '200';

	$options = array(
		CURLOPT_URL => GITHUB_API.$githubID,
		CURLOPT_USERAGENT => 'KAUST_Repo',
		CURLOPT_CUSTOMREQUEST => "GET"
	);

	$response = makeCurlRequest($options, $successCode);

	return $response;
}

function queryGithubGetReadME($githubID)
{
	$successCode = '200';

	$options = array(
		CURLOPT_URL => GITHUB_API.$githubID.'/readme',
		CURLOPT_USERAGENT => 'KAUST_Repo',
		CURLOPT_CUSTOMREQUEST => "GET"
	);

	$response = makeCurlRequest($options, $successCode);

	return $response;
}

