<?php
/*

**** This function is responsible for setting the visibility of an org based on its type and ID.

** Parameters :
	$orgType: the org type as a string
	$orgID: the org ID as a string

** Return: the org visibility as a string

*/
//-----------------------------------------------------------------------------------------------------------

function setOrgVisibility($orgType, $orgID)
{
	//prepare empty orgVisibility variable
	$orgVisibility = '';
	
	//Org types to mark with public visibility (this determines whether they show up in the public portal in Pure)		
	$publicTypes = array('academicDivision', 'centerOfExcellence', 'corelab', 'corelabunit', 'program', 'university');
	
	//Core Labs will be marked as public
	$otherPublicOrgIDs = ['30000031'];
	
	//Add Research & Graduate Education
	$otherPublicOrgIDs[] = '30000001';
	
	//Add Office of the Provost
	//$otherPublicOrgIDs[] = '30000046';
	
	//Add Office of the Provost subunit
	//$otherPublicOrgIDs[] = '30001154';
	
	//Add Research Department
	$otherPublicOrgIDs[] = '30001600';
	
	//Add Research Division
	$otherPublicOrgIDs[] = '30000167';

	//Add Research Operations (parent unit of Centers of Excellence)
	$otherPublicOrgIDs[] = '30002371';
	
	//hard-coded list of org IDs of orgs that may have a public org type, but should be marked as internal
	$otherRestrictedOrgIDs = array();

	//Add Data Science and Analytics program
	$otherRestrictedOrgIDs[] = '30002149';

	//Add Research Infrastructure Operations
	$otherRestrictedOrgIDs[] = '30001352';

	//Add subunits of Research Infrastructure Operations
	$otherRestrictedOrgIDs[] = '30001579';
	$otherRestrictedOrgIDs[] = '30001578';

	//If the org is a public type, or in the list of other public IDs, mark it as public
	if(in_array($orgType, $publicTypes) || in_array($orgID, $otherPublicOrgIDs))
	{
		$orgVisibility = 'public';

		//If the org is in the list of other restricted IDs, mark it as internal
		if(in_array($orgID, $otherRestrictedOrgIDs))
		{
			$orgVisibility = 'internal';
		}
	}
	else
	{
		$orgVisibility = 'internal';
	}
	
	//return org visibility
	return $orgVisibility;
}
