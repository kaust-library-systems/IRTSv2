<?php
	//Define function to add an internal person
	function addInternalPerson($person)
	{
		global $purexml, $message;
		
		$nameParts = explodeName($person['controlName']);
		
		//Set student number as PureID for individuals who have both a personnel number and a student number
		if(!empty($person['studentNumber']))
		{
			$pureid = $person['studentNumber'];									
		}
		elseif(!empty($person['personnelNumber']))
		{
			$pureid = $person['personnelNumber'];
		}		
		
		if(!empty($pureid))
		{
			$purexml .= '<v1:person id="'.$pureid.'" origin="internal">';
		}
		else
		{
			$purexml .= '<v1:person origin="unknown">';
		}

		addName($nameParts);
		
		$purexml .= '</v1:person>';
	}	
