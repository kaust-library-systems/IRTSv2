<?php
	//Define function to process a person record
	function processPersonRecord($input, $purexml)
	{
		global $irts, $report;
		
		$currentFaculty = FALSE;
		$currentAcademic = FALSE;

		$v1NameSpace = 'v1.unified-person-sync.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		$output = $purexml->addChild('person');
		
		//$output->addAttribute('id', $input['personID']);
		$output->addAttribute('id', $input['personnelNumber']);
		
		$nameElement = $output->addChild('name');
		
		if(!empty($input['preferredFirstName']))
		{
			$nameElement->addChild('firstname', htmlspecialchars($input['preferredFirstName']), $v3NameSpace);
		}
		else
		{
			$nameElement->addChild('firstname', htmlspecialchars($input['firstName']), $v3NameSpace);
		}
		
		$nameElement->addChild('lastname', htmlspecialchars($input['lastName']), $v3NameSpace);
		
		$output->addChild('gender', $input['gender']);
		
		$output->addChild('nationality', $input['nationality']);
		$output->addChild('employeeStartDate', $input['employeeStartDate']);
		
		if(!empty($input['systemLeavingDate']))
		{
			$output->addChild('systemLeavingDate', $input['systemLeavingDate']);
		}
		
		$organisationAssociationsElement = $output->addChild('organisationAssociations');
		
		foreach($input['relations'] as $id => $relation)
		{
			$staffOrganisationAssociationElement = $organisationAssociationsElement->addChild('staffOrganisationAssociation');
			
			$staffOrganisationAssociationElement->addAttribute('id', $id);
			
			if(isset($relation['employmentType']))
			{
				$staffOrganisationAssociationElement->addChild('employmentType', str_replace(' ', '_', $relation['employmentType']));
				
				if($relation['endDate'] === '9999-12-31')
				{
					$staffOrganisationAssociationElement->addChild('primaryAssociation', 'true');
					
					if($relation['employmentType'] === 'Faculty')
					{
						$currentFaculty = TRUE;
					}					
				}
			}
			
			$organisationElement = $staffOrganisationAssociationElement->addChild('organisation');
			
			$organisationElement->addChild('source_id', $relation['orgID'], $v3NameSpace);
			
			$periodElement = $staffOrganisationAssociationElement->addChild('period');
			
			$periodElement->addChild('startDate', $relation['startDate'], $v3NameSpace);
			
			if($relation['endDate'] !== '9999-12-31')
			{
				$periodElement->addChild('endDate', $relation['endDate'], $v3NameSpace);
			}
			
			if(isset($relation['staffType']))
			{
				$staffOrganisationAssociationElement->addChild('staffType', str_replace('-', '', $relation['staffType']));
				
				if($relation['endDate'] === '9999-12-31')
				{
					if($relation['staffType'] === 'Academic')
					{
						$currentAcademic = TRUE;
					}
				}
			}
			
			if(isset($relation['jobTitle']))
			{
				$staffOrganisationAssociationElement->addChild('jobTitle', str_replace(' ', '_', $relation['jobTitle']));
			}
			
			if(isset($relation['jobDescription']))
			{
				$jobDescriptionElement = $staffOrganisationAssociationElement->addChild('jobDescription');
			
				$jobDescriptionElement->addChild('text', htmlspecialchars($relation['jobDescription']), $v3NameSpace);
			}
		}

		if(isset($input['username']))
		{
			$user = $output->addChild('user');
			
			$user->addAttribute('id', strtolower($input['username']).'@kaust.edu.sa');
			
			//$user->addAttribute('id', $input['personnelNumber'].'_'.htmlspecialchars($input['lastName']));
		}
		
		$personIdsElement = $output->addChild('personIds');
		
		if(isset($input['email']))
		{
			$idElement = $personIdsElement->addChild('id', $input['email'], $v3NameSpace);
			
			$idElement->addAttribute('type', 'email');
			
			$idElement->addAttribute('id', $input['email']);
		}
		
		if(!empty($input['kaustid']))
		{
			$idElement = $personIdsElement->addChild('id', $input['kaustid'], $v3NameSpace);
			
			$idElement->addAttribute('type', 'kaust_id');
			
			$idElement->addAttribute('id', $input['kaustid']);
		}
		
		if(isset($input['orcid']))
		{
			$output->addChild('orcId', $input['orcid']);
		}
		
		if($currentAcademic)
		{
			$output->addChild('visibility', 'Public');
		}
		else
		{
			$output->addChild('visibility', 'Restricted');
		}
		
		//<profiled>false</profiled>

		return array('purexml'=>$purexml,'output'=>$output);
	}
