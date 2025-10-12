<?php
/*

**** This function is responsible for setting the org type based on the org data.

** Parameters :
	$org: the org data as an array
	$orgLevel: the org level as an integer
	$orgParents: the org parents as an array

** Return: the org type as a string

*/
//-----------------------------------------------------------------------------------------------------------

function setOrgType($org, $orgLevel, $orgParents)
{
	$report = '';
	
	//prepare empty orgType variable
	$orgType = '';

	//Academic division IDs are needed to identify programs and research centers
	$academicDivisionIDs = array('30000171','30000283','30000284');

	//Core lab ID is needed to identify core labs
	$coreLabID = '30000031';
	
	$orgTypesByLevel = array(
		1 => "university", 
		2 => "sector", 
		3 => "division",
		4 => "office",
		5 => "officeunit",
		6 => "officeunitteam",
		7 => "officeunitteamgroup"
	);
	
	$coreLabOrgTypesByLevel = array(
		5 => "corelab",
		6 => "corelabunit"
	);

	if(($orgLevel === 3 && in_array($org['Id'], $academicDivisionIDs)))
	{
		$orgType = 'academicDivision';
	}
	elseif(($orgLevel === 4 && in_array($org['Parent Organization Id'], $academicDivisionIDs)))
	{
		if(stripos($org['Name'], 'Center') !== FALSE)
		{
			$orgType = 'researchcenter';
		}
		else
		{
			$orgType = 'program';
		}
	}
	elseif(stripos($org['Name'], 'Center of Excellence') !== FALSE)
	{
		$orgType = 'centerOfExcellence';
	}
	elseif(in_array($coreLabID, $orgParents))
	{
		if($orgLevel > 6)
		{
			$report .= $org['Id'].' - Core lab org level '.$orgLevel.' - org type set as "corelabunitteam"'.PHP_EOL;

			$orgType = 'corelabunitteam';
		}
		else
		{
			$orgType = $coreLabOrgTypesByLevel[$orgLevel];
		}
	}
	else
	{
		if($orgLevel > 7)
		{
			$report .= $org['Id'].' - Org level '.$orgLevel.' - org type set as "officeunitteamcell"'.PHP_EOL;

			$orgType = 'officeunitteamcell';
		}
		else
		{
			$orgType = $orgTypesByLevel[$orgLevel];
		}
	}

	//echo $report;
	
	return $orgType;
}
