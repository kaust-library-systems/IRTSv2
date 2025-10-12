<?php
	//The Web of Science Starter API documentation is at: https://developer.clarivate.com/apis/wos-starter	
	function queryWos($type, $value, $page = 1, $limit = 50)
	{
		if($type === 'affiliation')
		{
			$query = urlencode('OG=('.WOS_CONTROLLED_ORG_NAME.') OR OG=('.INSTITUTION_ABBREVIATION.')');
		}
		elseif($type === 'doi')
		{
			$query = 'DO=("'.$value.'")';
		}
		elseif($type === 'UT')
		{
			$query = 'UT=("'.$value.'")';
		}		
		elseif($type === 'authorID')
		{
			$query = 'AI=("'.$value.'")';
		}
		
		//Sort descending by load date allows us to exit when the first item is met that was already harvested from WOS
		$sort = 'LD+D';

		$url = WOS_API_URL."documents?q=$query&sortField=$sort&page=$page&limit=$limit";

		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>array("X-ApiKey: ".WOS_API_KEY)
			)
		);

		$context = stream_context_create($opts);

		$json = file_get_contents($url, false, $context);

		return $json;
	}
?>