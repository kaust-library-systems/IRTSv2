<?php
	//Define function to add an external person
	function addExternalPerson($nameParts)
	{
		global $purexml, $message;
		
		$purexml .= '<v1:person origin="external">';

		addName($nameParts);
		
		$purexml .= '</v1:person>';
	}	
