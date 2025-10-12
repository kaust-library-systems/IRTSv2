<?php
	//Define function to query the Scopus API
	function queryScopus($type, $value, $start = 0, $count = 10)
	{
		if($type === 'affiliation')
		{
			if(is_null($value))
			{
				$query = 'AF-ID('.SCOPUS_AF_ID.')';

				if(!empty(INSTITUTION_ABBREVIATION))
				{
					$query .= ' OR AFFIL("'.INSTITUTION_ABBREVIATION.'")';
				}

				if(!empty(INSTITUTION_CITY))
				{
					$query .= ' OR AFFIL("'.INSTITUTION_CITY.'")';
				}

				//$query .= ' AND DOCTYPE("bk")';
			}
		}
		elseif($type === 'funding')
		{
			if(is_null($value))
			{
				$query = 'FUND-ALL("'.INSTITUTION_NAME.'")';

				if(!empty(INSTITUTION_ABBREVIATION))
				{
					$query .= ' OR FUND-ALL("'.INSTITUTION_ABBREVIATION.'")';
				}
				$query .= 'AND NOT AF-ID('.SCOPUS_AF_ID.')';
			}
		}
		elseif($type === 'doi')
		{
			$query = 'DOI("'.$value.'")';
		}
		elseif($type === 'eid')
		{
			$query = 'EID("'.$value.'")';
		}		
		elseif($type === 'authorID')
		{
			$query = 'AU-ID("'.$value.'") AND NOT AF-ID('.SCOPUS_AF_ID.') AND NOT AF-ID(109435159)';
		}

		$query = urlencode($query);

		//$view = '&view=COMPLETE';

		//Sorting by original load date allows us to exit when the first item is met that is already in the scopus harvest table
		$sort = '&sort=-orig-load-date';

		$url = ELSEVIER_API_URL."search/scopus?start=$start&count=$count&query=$query$sort";

		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>array("Accept: application/xml", "X-ELS-APIKey: ".ELSEVIER_API_KEY, "X-ELS-Insttoken: ".ELSEVIER_INST_TOKEN)
			)
		);

		$context = stream_context_create($opts);

		$xml = file_get_contents($url, false, $context);

		return $xml;
	}
