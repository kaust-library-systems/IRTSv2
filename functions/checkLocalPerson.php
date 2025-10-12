<?php
	//Define function to look for an internal person
	function checkLocalPerson($person)
	{
		global $irts;
		
		$matchfound = '';		
		foreach($person as $key => $value)
		{
			$localPersonIDs = getValues($irts, setSourceMetadataQuery('local', NULL, NULL, $key, $irts->real_escape_string($value)), array('idInSource'), 'arrayOfValues');
			
			//accept result and leave loop if unique match found
			if(count($localPersonIDs) === 1)
			{
				$matchfound = 'yes';
				$localPersonID = $localPersonIDs[0];				
				break 1;
			}
		}
		
		if($matchfound==='yes')
		{
			$personFields = array('local.person.id','local.person.personnelNumber','local.person.studentNumber','local.person.name','local.person.email','dc.identifier.orcid');
				
			foreach($personFields as $personField)
			{
				$person[$personField] = getValues($irts, setSourceMetadataQuery('local', $localPersonID, NULL, $personField), array('value'), 'singleValue');
			}
		}
		
		return $person;
	}	
