<?php
	//Define function to process a grant record
	function processGrantRecord($input, $purexml)
	{
		global $irts, $report;

		$v1NameSpace = 'v1.upmaward.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		$output = $purexml->addChild('upmaward');

		$output->addAttribute('id', $input['ORA Reference']);
		
		$types = array(
			'Government - non KSA' => 'external_grant/non_ksa_government',
			'KAUST' => 'internal/programaward',
			'Government - KSA' => 'external_grant/ksa_government',
			'Industry' => 'industry/award',
			'Academic' => 'other/award',
			'Non profit' => 'other/nonprofit',
			'Other' => 'other/award');

		if(!empty($input['Sponsor Type']))
		{
			$output->addAttribute('type', $types[$input['Sponsor Type']]);
		}

		$titleElement = $output->addChild('title');
		
		$titleElement->addChild('text', substr(str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['Proposal title']), ENT_NOQUOTES)), 0, 256), $v3NameSpace);
		
		$idsElement = $output->addChild('ids');
		
		$idElement = $idsElement->addChild('id', $input['ORA Reference'], $v3NameSpace);
		
		$idElement->addAttribute('type', 'funderprojectreference');
		
		if(!empty($input['KAUST Investigator Division']))
		{
			$matchedDeptIDs = array_unique(getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.name.variant', $input['KAUST Investigator Division']), array('idInSource')));

			if(!empty($matchedDeptIDs))
			{
				if(count($matchedDeptIDs)===1)
				{
					$divisionID = str_replace('org_', '', $matchedDeptIDs[0]);
				}
			}
		}
		
		if(!empty($input['KAUST Investigator Center']))
		{
			$matchedDeptIDs = array_unique(getValues($irts, setSourceMetadataQuery('local', NULL, NULL, 'local.name.variant', $input['KAUST Investigator Center']), array('idInSource')));

			if(!empty($matchedDeptIDs))
			{
				if(count($matchedDeptIDs)===1)
				{
					$centerID = str_replace('org_', '',$matchedDeptIDs[0]);
				}
			}
		}

		$localInvestigatorNames = array(trim($input['KAUST Investigator Name']) => 'pi');
		
		$count = 1;
		
		while($count < 10)
		{
			if(!empty($input['KAUST CoI name '.$count]))
			{
				$localInvestigatorNames[trim($input['KAUST CoI name '.$count])] = 'coi';
			}
			$count++;
		}

		$internalAwardholdersElement = $output->addChild('internalAwardholders');

		foreach($localInvestigatorNames as $localInvestigatorName => $role)
		{
			$localInvestigatorName = transform('googleScholar', 'dc.contributor.author', '', $localInvestigatorName);
			
			$match = checkPerson(array("name" => $localInvestigatorName));
			
			if(!empty($match['personnelNumber']))
			{
				$pureid = $match['personnelNumber'];
		
				$internalAwardholderElement = $internalAwardholdersElement->addChild('internalAwardholder');
				
				$internalAwardholderElement->addChild('personId', $pureid);
				
				if(!empty($divisionID))
				{
					$organisationIdsElement = $internalAwardholderElement->addChild('organisationIds');
					
					$organisationElement = $organisationIdsElement->addChild('organisation');
					
					$organisationElement->addAttribute('id', $divisionID);
				}
				
				if(!empty($centerID))
				{
					$organisationElement = $organisationIdsElement->addChild('organisation');
					
					$organisationElement->addAttribute('id', $centerID);
				}
				
				$internalAwardholderElement->addChild('role', $role);
			}
			else
			{
				$report .= $localInvestigatorName.' id not found.'.PHP_EOL;
			}
		}
		
		$managedByOrganisationElement = $output->addChild('managedByOrganisation');
		
		if(isset($centerID))
		{
			$managedByOrganisationElement->addAttribute('id', $centerID);
		}
		elseif(isset($divisionID))
		{
			$managedByOrganisationElement->addAttribute('id', $divisionID);
		}
		else
		{
			//if no center or division ID, OSR will be listed as the managing org
			$managedByOrganisationElement->addAttribute('id', '30000054');
		}
		
		if(!empty($input['Actual Start date (YYYY-MM-DD)']))
		{
			$output->addChild('actualStartDate', date("Y-m-d", strtotime($input['Actual Start date (YYYY-MM-DD)'])));
		}
		
		if(!empty($input['Actual End date (YYYY-MM-DD)']))
		{
			$output->addChild('actualEndDate', date("Y-m-d", strtotime($input['Actual End date (YYYY-MM-DD)'])));
		}
		
		if(!empty($input['Award start date (YYYY-MM-DD)']))
		{
			$awardDate = $input['Award start date (YYYY-MM-DD)'];
			$awardDate = date("Y-m-d", strtotime($awardDate));
			$output->addChild('awardDate', $awardDate);
		}

		$financialFundingsElement = $output->addChild('financialFundings');
		
		$financialFundingElement = $financialFundingsElement->addChild('financialFunding');
		
		$financialFundingElement->addAttribute('id', 'finfunding');
		
		$financialFundingElement->addChild('externalOrgName', str_replace('&', '&amp;', $input['Sponsor']));
		
		$financialFundingElement->addChild('fundingProjectScheme', str_replace('&', '&amp;', $input['Funding Scheme']));
		
		if(!empty($input['Total Funding']))
		{
			$financialFundingElement->addChild('awardedAmount', str_replace(',','',str_replace('$','',$input['Total Funding'])));
		}

		$output->addChild('visibility', 'Restricted');

		return array('purexml'=>$purexml,'output'=>$output);
	}
