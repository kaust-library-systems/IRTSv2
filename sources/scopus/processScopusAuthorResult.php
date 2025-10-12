<?php
	//Define function to process a full author record retrieved from the Scopus API (http://api.elsevier.com/documentation/AuthorRetrievalAPI.wadl)
	function processScopusAuthorResult($xml, $identifier)
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
		
		if(isset($xml->{'author-profile'}))
		{		
			$names = array();
			
			$preferredName = $xml->{'author-profile'}->{'preferred-name'};
			array_push($names, $preferredName->{'surname'}.', '.$preferredName->{'given-name'});
			array_push($names, $preferredName->{'surname'}.', '.$preferredName->{'initials'});
			
			if(isset($xml->{'author-profile'}->{'name-variant'}))
			{
				foreach($xml->{'author-profile'}->{'name-variant'} as $nameVariant)
				{
					if(!in_array($nameVariant->{'surname'}.', '.$nameVariant->{'initials'}, $names))
					{
						array_push($names, $nameVariant->{'surname'}.', '.$nameVariant->{'initials'});
					}
					if(!in_array($nameVariant->{'surname'}.', '.$nameVariant->{'given-name'}, $names))
					{
						array_push($names, $nameVariant->{'surname'}.', '.$nameVariant->{'given-name'});
					}
				}
			}
			
			foreach($names as $name)
			{
				insert($pubs, 'scopusAuthorNameVariants', array("authorID", "nameVariant"), array($identifier, $name));
			}
		}	
	}
