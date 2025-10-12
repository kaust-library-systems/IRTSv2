<?php
	//Define function to process a full affiliation record retrieved from the Scopus API (http://api.elsevier.com/documentation/AffiliationRetrievalAPI.wadl)
	function processScopusAffiliationResult($xml, $identifier)
	{
		global $pubs, $today, $message;
		
		$originalXML = $xml;
		
		//Strip namespaces due to problems in accessing elements with namespaces even with xpath, temporary solution?
		//Could also retrieve JSON instead?
		$xml = str_replace('dc:', '', $xml);
		$xml = str_replace('opensearch:', '', $xml);
		$xml = str_replace('prism:', '', $xml);
		$xml = str_replace('ce:', '', $xml);
		
		$xml = simplexml_load_string($xml);
		
		if(isset($xml->{'institution-profile'}))
		{		
			$names = array();
			
			$preferredName = $xml->{'institution-profile'}->{'preferred-name'};
			array_push($names, $nameVariant);
			
			if(isset($xml->{'institution-profile'}->{'name-variant'}))
			{
				foreach($xml->{'institution-profile'}->{'name-variant'} as $nameVariant)
				{
					if(!in_array($nameVariant, $names))
					{
						array_push($names, $nameVariant);
					}
				}
			}
			
			foreach($names as $name)
			{
				insert($pubs, 'scopusAffiliationNameVariants', array("afID", "nameVariant"), array($identifier, $name));
			}
		}	
	}
