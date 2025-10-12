<?php
/*

**** This function assigns names to the referrer strings of known (most common) referrers.

** Parameters :
	$referrerURL: the URL of the referrer
	
** Return:
	$referrerName: the name of the referrer

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function assignReferrerName($referrerURL)
{
	if(empty($referrerURL) || $referrerURL == '(direct)'){
		return 'Unknown';
	}
	
	$referrers = [
		'scholar.google' => 'Google Scholar',
		'webofknowledge' => 'Web of Science',
		'webofscience' => 'Web of Science',
		'pubmed' => 'PubMed',
		'crossref' => 'CrossRef',
		'yandex' => 'Yandex',
		'bing' => 'Bing',
		'yahoo' => 'Yahoo',
		'baidu' => 'Baidu',
		'duckduckgo' => 'DuckDuckGo',
		'semanticscholar' => 'Semantic Scholar',
		'ablesci.com' => 'AbleSci',
		'worldcat.org' => 'WorldCat',
		'base-search.net' => 'BASE',
		'openaire.eu' => 'OpenAIRE',
		'unpaywall.org' => 'Unpaywall',
		'core.ac.uk' => 'CORE',
		'europepmc.org' => 'Europe PMC',
		'oatd.org' => 'OATD',
		'repository.kaust.edu.sa' => 'KAUST Repository',
		'waseet.kaust.edu.sa' => 'KAUST SSO',
		'cemse.kaust.edu.sa' => 'CEMSE Division Site',
		'library.kaust.edu.sa' => 'KAUST Library',
		'academia.kaust.edu.sa' => 'KAUST Pure Portal',
		'faculty.kaust.edu.sa' => 'KAUST Pure Portal',
		'kaust.edu.sa' => 'Other KAUST Websites',
		'google' => 'Google'
	];

	foreach ($referrers as $referrerString => $referrerName) {
		if (strpos($referrerURL, $referrerString) !== FALSE) {
			return $referrerName;
		}
	}

	if (strpos($referrerURL, '//') !== FALSE) {
		// Get the domain name from the referrer URL, maximum 50 characters
		$referrerName = substr(explode('/', explode('//', $referrerURL)[1])[0], 0, 50);

		return $referrerName;
	}

	return 'Other';
}