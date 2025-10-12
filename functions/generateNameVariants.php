<?php
	//Define function to generate and save possible name variants for a given person
	function generateNameVariants($idInSource, $name)
	{
		global $irts;
		
		$source = 'local';
		
		$variantsAdded = array();
		
		//the submitted name will also be saved as a variant as it will not always be the name saved as the standard name for the person
		$nameVariants[] = $name;
		
		//Get the highest place entry for existing variants for this person
		$place = getValues($irts, "SELECT `place` FROM `metadata` 
								WHERE source = '$source' 
								AND idInSource = '$idInSource' 
								AND `field` = 'local.name.variant' 
								AND deleted IS NULL 
								ORDER BY `place` 
								DESC LIMIT 1", array('place'), 'singleValue');
		
		$surnamePrefixes = Array("Al", "El", "Bin", "Abdul", "Abdel");	
		
		$nameParts = explode(', ', $name);
		$familyName = $nameParts[0];
		$givenName = $nameParts[1];
		
		$familyNameVariants = Array($familyName);
		if(strpos($familyName, ' ')!==FALSE)
		{	
			$familyNameParts = explode(' ', $familyName);
			$familyNameVariants[] = implode('-', $familyNameParts);
			$familyNameVariants[] = implode('', $familyNameParts);
		}
		elseif(strpos($familyName, '-')!==FALSE)
		{	
			$familyNameParts = explode('-', $familyName);
			$familyNameVariants[] = implode(' ', $familyNameParts);
			$familyNameVariants[] = implode('', $familyNameParts);
		}
		else
		{
			foreach($surnamePrefixes as $surnamePrefix)
			{
				if(substr($familyName, 0, strlen($surnamePrefix))===$surnamePrefix)
				{
					$familyNameParts = Array();
					$familyNameParts[] = substr($familyName, 0, strlen($surnamePrefix));
					$familyNameParts[] = substr($familyName, strlen($surnamePrefix));
					$familyNameVariants[] = implode(' ', $familyNameParts);
					$familyNameVariants[] = implode('-', $familyNameParts);
				}
			}
		}
		
		$givenNameVariants = Array($givenName);
		$givenNameVariants[] = $givenName[0];
		$givenNameVariants[] = $givenName[0].'.';
		
		if(strpos($givenName, ' ')!==FALSE)
		{	
			$givenNameParts = explode(' ', $givenName);
			$givenNameVariants[] = $givenNameParts[0];
			
			$givenNameInitials = Array();
			foreach($givenNameParts as $givenNamePart)
			{
				if(!empty($givenNamePart))
				{
					$givenNameInitials[] = $givenNamePart[0];
				}
			}
			
			if(strpos($givenNameParts[1], '.')===FALSE)
			{
				$givenNameVariants[] = implode('-', $givenNameParts);
				$givenNameVariants[] = implode('', $givenNameParts);
			}
		}
		elseif(strpos($givenName, '-')!==FALSE)
		{	
			$givenNameParts = explode('-', $givenName);
			$givenNameVariants[] = $givenNameParts[0];
			
			$givenNameInitials = Array();
			foreach($givenNameParts as $givenNamePart)
			{
				$givenNameInitials[] = $givenNamePart[0];
			}
			
			if(strpos($givenNameParts[1], '.')===FALSE)
			{
				$givenNameVariants[] = implode(' ', $givenNameParts);
				$givenNameVariants[] = implode('', $givenNameParts);
			}
		}
		else
		{
			$givenNameParts = Array();
			$givenNameInitials = Array($givenName[0]);
			$givenNameVariants[] = $givenName[0];
			$givenNameVariants[] = $givenName[0].'.';
		}
		
		if(count($givenNameInitials)>1)
		{
			if(count($givenNameParts)>2)
			{
				$givenNameVariants[] = $givenNameParts[0].' '.implode('. ', $givenNameInitials).'.';
				$givenNameVariants[] = $givenNameParts[0].' '.implode('. ', $givenNameInitials);
				$givenNameVariants[] = $givenNameParts[0].' '.$givenNameParts[1];
			}
			
			$givenNameVariants[] = $givenNameParts[0].' '.$givenNameInitials[1].'.';
			$givenNameVariants[] = $givenNameParts[0].' '.$givenNameInitials[1];			
			$givenNameVariants[] = implode('. ', $givenNameInitials).'.';
			$givenNameVariants[] = implode('.-', $givenNameInitials).'.';
			$givenNameVariants[] = implode('.', $givenNameInitials).'.';
			$givenNameVariants[] = implode(' ', $givenNameInitials);
			$givenNameVariants[] = implode('-', $givenNameInitials);
			$givenNameVariants[] = implode('', $givenNameInitials);
		}
		
		array_unique($givenNameVariants);
		array_unique($familyNameVariants);
		
		foreach($familyNameVariants as $familyNameVariant)
		{
			foreach($givenNameVariants as $givenNameVariant)
			{
				$nameVariants[] = $familyNameVariant.', '.$givenNameVariant;
			}
		}
		
		foreach($nameVariants as $nameVariant)
		{
			$escapedNameVariant = $irts->real_escape_string($nameVariant);
					
			//Check for existing nameVariant entry for this localID
			$existing = getValues($irts, "SELECT rowID FROM `metadata` WHERE source='$source' AND idInSource = '$idInSource' AND (field='local.name.variant' OR field='local.person.name') AND value = '$escapedNameVariant' AND deleted IS NULL", array('rowID'), 'arrayOfValues');
			
			if(empty($existing))
			{
				$place++;
				$field = 'local.name.variant';
				$rowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $nameVariant, NULL);
				$variantsAdded[] = $nameVariant;
			}
		}
		
		return $variantsAdded;
	}
