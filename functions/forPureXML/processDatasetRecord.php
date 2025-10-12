<?php
	//Define function to process a dataset record
	function processDatasetRecord($input, $purexml)
	{
		global $irts, $report;

		$v1NameSpace = 'v1.dataset.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		$output = $purexml->addChild('dataset');

		$handleURL = $input['dc.identifier.uri'][0]['value'];

		$output->addAttribute('id', $handleURL);

		$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.uri', $handleURL), array('idInSource'), 'singleValue');

		$output->addAttribute('type', "dataset");

		$titleElement = $output->addChild('title', substr(str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.title'][0]['value']), ENT_NOQUOTES)), 0, 256));

		if(strlen($input['dc.date.issued'][0]['value'])===4)
		{
			$associationStartDate = $input['dc.date.issued'][0]['value'].'-01-01';
		}
		elseif(strlen($input['dc.date.issued'][0]['value'])===7)
		{
			$associationStartDate = $input['dc.date.issued'][0]['value'].'-01';
		}
		else
		{
			$associationStartDate = substr($input['dc.date.issued'][0]['value'], 0, 10);
		}

		//Treat both dc.description and dc.description.abstract as description
		if(!empty($input['dc.description.abstract'][0]['value']))
		{
			$output->addChild('description', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.description.abstract'][0]['value']), ENT_NOQUOTES)));
		}
		else
		{
			$description = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.description'), array('value'), 'singleValue');
			if(!empty($description))
			{
				$output->addChild('description', str_replace('&', '&amp;', htmlspecialchars(strip_tags($description), ENT_NOQUOTES)));
			}
		}

		$corporateAuthors = array();
		$allAuthorsDeptIds = array();
		$ownerid = '';
		$personsElement = $output->addChild('persons');
		foreach($input['dc.contributor.author'] as $author)
		{
			$match = array();
			
			$pureid = '';
			
			$localAff = FALSE;

			$thisAuthorDeptIds = array();

			$personElement = $personsElement->addChild('person');
			
			$personElement->addChild('role', 'creator');
			$personElement->addChild('associationStartDate', $associationStartDate);
			
			$personPersonElement = $personElement->addChild('person');
			
			if(strpos($author['value'],', ')!==FALSE)
			{
				$nameParts = explodeName($author['value']);
				
				$personPersonElement->addChild('firstName', $nameParts['firstName']);
				$personPersonElement->addChild('lastName', $nameParts['lastName']);
			}
			else
			{
				//We assume that if author name does not have lastName, firstName form that it is a corporate author, they will be added with their full name in both places
				$personPersonElement->addChild('firstName', $author['value']);
				$personPersonElement->addChild('lastName', $author['value']);
			}
			
			if(!empty($author['children']['dc.identifier.orcid'][0]['value']))
			{
				$match = checkPerson(array("orcid" => $author['children']['dc.identifier.orcid'][0]['value']));
			}

			$kaustPersons = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'kaust.person'), array('value'), 'arrayOfValues');

			if(in_array($author['value'], $kaustPersons))
			{
				$localAff = TRUE;
				if(empty($match['localID']))
				{
					$match = checkPerson(array("name" => $author['value']));
				}
			}

			//Add pure person id if there was a local match whether based on scopus author id, orcid id, or on existence of a local affiliation
			if(!empty($match['localID']))
			{
				//$report .= print_r($match, TRUE).PHP_EOL;

				//Set student number as PureID for individuals who have both a personnel number and a student number
				if(!empty($match['studentNumber']))
				{
					$pureid = $match['studentNumber'];
				}
				elseif(!empty($match['personnelNumber']))
				{
					$pureid = $match['personnelNumber'];
				}
				else
				{
					$report .= ' - Author marked as KAUST Person, but no internal person match, marking as external origin!!!'.PHP_EOL;
				}
			}

			if(!empty($pureid))
			{
				$personElement->addAttribute('id', $pureid);

				$personPersonElement->addAttribute('lookupId', $pureid);

				$personPersonElement->addAttribute('origin', "internal");

				if($localAff)
				{
					$thisAuthorDeptIds = checkDeptIDs($match['localID'], $associationStartDate);
					$allAuthorsDeptIds = array_merge($allAuthorsDeptIds, $thisAuthorDeptIds);

					if(!empty($thisAuthorDeptIds))
					{
						$personOrganisationsElement = $personElement->addChild('organisations');
						foreach($thisAuthorDeptIds as $thisAuthorDeptId)
						{
							$personOrganisationsOrganisationElement = $personOrganisationsElement->addChild('organisation');

							$personOrganisationsOrganisationElement->addAttribute('lookupId', $thisAuthorDeptId);
						}
					}
				}
			}
			else
			{
				$externalPersonID = 'external'.rand().rand();

				$personElement->addAttribute('id', $externalPersonID);

				$personPersonElement->addAttribute('origin', "external");
			}
		}

		$issueDateParts = explode('-',$input['dc.date.issued'][0]['value']);
		$availableDateElement = $output->addChild('availableDate');
		$availableDateElement->addChild('year', $issueDateParts[0], $v3NameSpace);

		if(!empty($issueDateParts[1]))
		{
			$availableDateElement->addChild('month', $issueDateParts[1], $v3NameSpace);
		}
		if(!empty($issueDateParts[2]))
		{
			$availableDateElement->addChild('day', substr($issueDateParts[2], 0, 2), $v3NameSpace);
		}

		//Determine and add managing organisation
		$managingOrganisationElement = $output->addChild('managingOrganisation');

		$ownerID = owner($allAuthorsDeptIds);

		$managingOrganisationElement->addAttribute('lookupId', $ownerID);

		$publisherElement = $output->addChild('publisher');
		if(!empty($input['dc.publisher'][0]['value']))
		{
			$publisher = $input['dc.publisher'][0]['value'];
		}
		else
		{
			$publisher = 'KAUST Research Repository';
		}
		$publisherElement->addChild('name', htmlspecialchars($publisher));
		$publisherElement->addChild('type', 'publisher');

		$publisherElement->addAttribute('lookupId', 'publisher'.rand().rand());

		if(!empty($input['dc.identifier.doi']))
		{
			foreach(array_column($input['dc.identifier.doi'], 'value') as $doi)
			{
				$output->addChild('DOI', $doi);
			}
		}

		$linksElement = $output->addChild('links');
		$linkElement = $linksElement->addChild('link');
		$linkElement->addAttribute('id', 'link1');
		$linkElement->addChild('url', $handleURL);

		$output->addChild('visibility', 'Public');

		$relatedPublicationHandles = array();
		
		// The dc.relation.issupplementto field is not included in the dataset template, so we will retrieve it directly
		$relatedPublications = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.relation.issupplementto'), array('value'), 'arrayOfValues');
		
		foreach($relatedPublications as $relatedPublication)
		{
			if(strpos($relatedPublication, 'DOI:')!==FALSE)
			{
				$idInSource = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, "dc.identifier.doi", str_replace('DOI:', '', $relatedPublication)), array('idInSource'), 'singleValue');

				if(!empty($idInSource))
				{
					$relatedPublicationHandles[] = $idInSource;
				}
			}
		}

		if(!empty($relatedPublicationHandles))
		{
			$relatedPublicationsElement = $output->addChild('relatedPublications');
			foreach($relatedPublicationHandles as $relatedPublicationHandle)
			{
				$relatedPublicationsElement->addChild('relatedPublicationId', 'http://hdl.handle.net/'.$relatedPublicationHandle);
			}
		}

		return array('purexml'=>$purexml,'output'=>$output);
	}
