<?php
	//Define function to process a thesis or dissertation record
	function processTDRecord($input, $purexml)
	{
		global $irts, $report;

		$v1NameSpace = 'v1.studentthesis-sync.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		if($input['thesis.degree.grantor'][0]['value']==='King Abdullah University of Science and Technology')
		{
			$output = $purexml->addChild('studentThesis');

			if(!empty($input['dc.identifier.uri'][0]['value']))
			{
				$output->addAttribute('id', $input['dc.identifier.uri'][0]['value']);
			}

			if($input['dc.type'][0]['value']=='Thesis')
			{
				$output->addAttribute('type', 'master');
			}
			elseif($input['dc.type'][0]['value']=='Dissertation')
			{
				$output->addAttribute('type', 'doc');
			}

			$output->addAttribute('managedInPure', 'false');

			$output->addChild('title', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.title'][0]['value']), ENT_NOQUOTES)));

			$output->addChild('language', 'en_US');

			$dateParts = explode('-', $input['dc.date.issued'][0]['value']);

			$awardDateElement = $output->addChild('awardDate');

			$awardDateElement->addChild('year', $dateParts[0], $v3NameSpace);

			if(!empty($dateParts[1]))
			{
				$awardDateElement->addChild('month', $dateParts[1], $v3NameSpace);
			}
			if(!empty($dateParts[2]))
			{
				$awardDateElement->addChild('day', $dateParts[2], $v3NameSpace);
			}

			if(!empty($input['dc.description.abstract'][0]['value']))
			{
				$abstractElement = $output->addChild('abstract');

				$textElement = $abstractElement->addChild('text', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.description.abstract'][0]['value']), ENT_NOQUOTES)), $v3NameSpace);
			}

			$authorsElement = $output->addChild('authors');

			foreach($input['dc.contributor.author'] as $author)
			{
				$person = array("name" => $author['value']);

				if(!empty($author['children']['dc.identifier.orcid'][0]['value']))
				{
					$person["orcid"] = $author['children']['dc.identifier.orcid'][0]['value'];
				}

				if(!empty($input['dc.person.id'][0]['value']))
				{
					$person["localID"] = $input['dc.person.id'][0]['value'];
				}

				//$report .= $input['dc.identifier.uri'][0]['value'].' - Sent person: '.print_r($person, TRUE).PHP_EOL;

				$person = checkPerson($person);

				//$report .= ' - Returned person: '.print_r($person, TRUE).PHP_EOL;

				//Set student number as PureID for individuals who have both a personnel number and a student number
				if(!empty($person['studentNumber']))
				{
					$pureid = $person['studentNumber'];
				}
				elseif(!empty($person['personnelNumber']))
				{
					$pureid = $person['personnelNumber'];
				}
				else
				{
					$pureid = '';
				}

				$authorElement = $authorsElement->addChild('author');

				if(!empty($pureid))
				{
					$authorElement->addAttribute('id', $pureid);
				}

				$personElement = $authorElement->addChild('person');

				if(!empty($pureid))
				{
					$personElement->addAttribute('lookupId', $pureid);
				}

				$nameParts = explodeName($author['value']);

				$personElement->addChild('firstName', $nameParts['firstName']);
				$personElement->addChild('lastName', $nameParts['lastName']);

				if(!empty($person['localID']))
				{
					$organisationsElement = $authorElement->addChild('organisations');

					$deptIDs = checkDeptIDs($person['localID'], $input['dc.date.issued'][0]['value']);

					if(!empty($deptIDs))
					{
						foreach($deptIDs as $deptID)
						{
							$organisationElement = $organisationsElement->addChild('organisation');

							$organisationElement->addAttribute('lookupId', $deptID);
						}
					}
					else
					{
						$organisationElement = $organisationsElement->addChild('organisation');

						//add highest level KAUST org id
						$organisationElement->addAttribute('lookupId', '30000085');
					}
				}

				$authorElement->addChild('role', 'author');
			}

			if(!isset($deptIDs))
			{
				$deptIDs = array();

				$report .= $input['dc.identifier.uri'][0]['value'].' - No Dept IDs for: '.print_r($person, TRUE).PHP_EOL;
			}

			$supervisorsElement = $output->addChild('supervisors');

			foreach($input['dc.contributor.advisor'] as $supervisor)
			{
				$person = array("name" => $supervisor['value']);

				if(!empty($supervisor['children']['dc.identifier.orcid'][0]['value']))
				{
					$person["orcid"] = $supervisor['children']['dc.identifier.orcid'][0]['value'];
				}

				$person = checkPerson($person);

				//Set student number as PureID for individuals who have both a personnel number and a student number
				if(!empty($person['studentNumber']))
				{
					$pureid = $person['studentNumber'];
				}
				elseif(!empty($person['personnelNumber']))
				{
					$pureid = $person['personnelNumber'];
				}
				else
				{
					$pureid = '';
				}

				$thesisSupervisorElement = $supervisorsElement->addChild('thesisSupervisor');

				if(!empty($pureid))
				{
					$thesisSupervisorElement->addAttribute('id', $pureid);
				}

				$thesisSupervisorElement->addAttribute('source', 'internalsource');

				$personElement = $thesisSupervisorElement->addChild('person');

				if(!empty($pureid))
				{
					$personElement->addAttribute('lookupId', $pureid);
				}

				$nameParts = explodeName($supervisor['value']);

				$personElement->addChild('firstName', $nameParts['firstName']);
				$personElement->addChild('lastName', $nameParts['lastName']);

				$thesisSupervisorElement->addChild('role', 'supervisor');
			}

			$ownerID = owner($deptIDs);

			$awardingInstitutionsElement = $output->addChild('awardingInstitutions');

			$awardingInstitutionElement = $awardingInstitutionsElement->addChild('awardingInstitution');

			$awardingInstitutionElement->addAttribute('id', $ownerID);

			$awardingInstitutionElement->addAttribute('source', 'internalsource');

			$organisationElement = $awardingInstitutionElement->addChild('organisation');

			$organisationElement->addAttribute('lookupId', $ownerID);

			//Add library as managing organisation
			$managingOrganisationElement = $output->addChild('managingOrganisation');

			$managingOrganisationElement->addAttribute('lookupId', '30000068');

			if(isset($input['dc.subject']))
			{
				$keywordsElement = $output->addChild('keywords');

				foreach($input['dc.subject'] as $keyword)
				{
					$keywordsElement->addChild('keyword', str_replace('&', '&amp;', htmlspecialchars(strip_tags($keyword['value']), ENT_NOQUOTES)), $v3NameSpace);
				}
			}

			$linksElement = $output->addChild('links');

			$linkElement = $linksElement->addChild('link', NULL, $v3NameSpace);

			$linkElement->addAttribute('id', $input['dc.identifier.uri'][0]['value']);

			$linkElement->addChild('url', $input['dc.identifier.uri'][0]['value'], $v3NameSpace);

			$descriptionElement = $linkElement->addChild('description', NULL, $v3NameSpace);

			$descriptionElement->addChild('text', 'KAUST Repository', $v3NameSpace);

			$bibliographicalNoteElement = $output->addChild('bibliographicalNote');

			$bibliographicalNoteElement->addChild('text', '{bibliographicalNotePlaceholder}', $v3NameSpace);
			
			//Mark as approved in workflow so they show in Pure as "Validated"
			$output->addChild('workflow','approved');
		}
		else
		{
			$output = NULL;
		}

		return array('purexml'=>$purexml,'output'=>$output);
	}
