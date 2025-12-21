<?php
	//Define function to query the Scopus API
	function getScopusCitationCount($idType, $ids)
	{
		$url = ELSEVIER_API_URL."abstract/citation-count?".$idType."=".$ids."&httpAccept=application/json";

		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>array("Accept: application/json", "X-ELS-APIKey: ".ELSEVIER_API_KEY, "X-ELS-Insttoken: ".ELSEVIER_INST_TOKEN)
			)
		);

		$context = stream_context_create($opts);

		$json = file_get_contents($url, false, $context);

		return $json;
	}
