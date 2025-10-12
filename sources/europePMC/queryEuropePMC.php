<?php
	//Define function to query the Europe PMC API (documentation at: https://europepmc.org/RestfulWebService )
	function queryEuropePMC($queryType, $value, $nextCursorMark = NULL)
	{
		$queryFields = array('affiliation'=>'AFF','funding'=>'ACK_FUND');
		
		if(is_null($value))
		{
			$field = $queryFields[$queryType];
			
			//Add first name in list to query
			$query = 'query=('.$field.':"'.INSTITUTION_NAME.'")';
			
			if(!empty(INSTITUTION_ABBREVIATION))
			{
				$query .= ' OR ('.$field.':"'.INSTITUTION_ABBREVIATION.'")';
			}
			
			if(!empty(INSTITUTION_CITY))
			{
				$query .= ' OR ('.$field.':"'.INSTITUTION_CITY.'")';
			}
			
			$query .= ' sort_date:y';
		}
		else
		{	
			$query = "query=$queryType:$value";
		}			
	
		$url = EUROPEPMC_API_URL.'search?'.str_replace(' ', '+', $query).'&resulttype=core&pageSize=50&format=json';
		
		if(!is_null($nextCursorMark))
		{
			$url .= '&cursorMark='.$nextCursorMark;
		}

		echo $url.PHP_EOL;

		return json_decode(file_get_contents($url), TRUE);		
	}
