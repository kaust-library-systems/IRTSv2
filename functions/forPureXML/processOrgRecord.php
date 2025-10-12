<?php
	//Define function to process an org record
	function processOrgRecord($input, $purexml)
	{
		global $irts, $report;

		$v1NameSpace = 'v1.organisation-sync.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		$output = $purexml->addChild('organisation');
		
		$output->addChild('organisationId', $input['orgID']);
		
		$output->addChild('type', $input['type']);
		
		$nameElement = $output->addChild('name');
		
		$nameElement->addChild('text', htmlspecialchars($input['name']), $v3NameSpace);
		
		$output->addChild('startDate', $input['startDate']);
		
		if(isset($input['endDate']))
		{
			$output->addChild('endDate', $input['endDate']);
		}
		$output->addChild('visibility', $input['visibility']);
		
		if(isset($input['parentOrgID']))
		{
			$output->addChild('parentOrganisationId', $input['parentOrgID']);
		}
		
		$nameVariantsElement = $output->addChild('nameVariants');
		
		$nameVariantElement = $nameVariantsElement->addChild('nameVariant');
		
		$nameVariantElement->addChild('type', 'shortname');
		
		$nameElement = $nameVariantElement->addChild('name');
		
		$nameElement->addChild('text', htmlspecialchars($input['shortName']), $v3NameSpace);
		
		$idsElement = $output->addChild('ids');
		
		$idElement = $idsElement->addChild('id');
		
		$idElement->addChild('idSource', 'organisationid');
		
		$idElement->addChild('id', $input['orgID']);
		
		if(isset($input['alternateID']))
		{
			$idElement = $idsElement->addChild('id');
		
			$idElement->addChild('idSource', 'organisationid');
			
			$idElement->addChild('id', $input['alternateID']);
		}

		return array('purexml'=>$purexml,'output'=>$output);
	}
