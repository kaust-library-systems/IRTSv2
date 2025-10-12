<?php
	//Define function to process a publication record
	function processPublicationRecord($input, $purexml, $authorID, $ownerID)
	{
		global $irts, $report, $localPersonsScopusIDs;

		$v1NameSpace = 'v1.publication-import.base-uk.pure.atira.dk';
		$v3NameSpace = 'v3.commons.pure.atira.dk';

		if(isset($input['dc.type'][0]['value']))
		{
			if(!in_array($input['dc.type'][0]['value'], array('Book', 'Book Chapter', 'Chapter', 'Conference Paper')))
			{
				$type = 'contributionToJournal';
				$subType = 'article';
				$peerReviewed = 'true';
			}
			elseif($input['dc.type'][0]['value']==='Book')
			{
				$type = 'book';
				$subType = 'book';
				$peerReviewed = 'false';
			}
			else
			{
				$type = 'chapterInBook';

				$peerReviewed = 'false';
				if(in_array($input['dc.type'][0]['value'], array('Book Chapter', 'Chapter')))
				{
					$subType = 'chapter';
				}
				elseif($input['dc.type'][0]['value'] === 'Conference Paper')
				{
					$subType = 'conference';
				}
			}
		}
		else
		{
			$type = 'contributionToJournal';
			$subType = 'article';
			$peerReviewed = 'true';
		}

		$output = $purexml->addChild($type);

		if(!empty($input['dc.identifier.uri'][0]['value']))
		{
			$handleURL = $input['dc.identifier.uri'][0]['value'];
			
			$output->addAttribute('id', $handleURL);

			$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.uri', $handleURL), array('idInSource'), 'singleValue');
		}
		elseif(empty($input['dc.identifier.uri'][0]['value'])&&!empty($input['dc.identifier.doi'][0]['value']))
		{
			$handle = getValues($irts, setSourceMetadataQuery('repository', NULL, NULL, 'dc.identifier.doi', $input['dc.identifier.doi'][0]['value']), array('idInSource'), 'singleValue');

			$handleURL = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.uri'), array('value'), 'singleValue');
			
			if(!empty($handleURL))
			{
				$output->addAttribute('id', $handleURL);
			}
		}
		
		if(empty($handleURL)&&empty($handle))
		{
			if(!empty($input['dc.identifier.eid'][0]['value']))
			{
				$output->addAttribute('id', $input['dc.identifier.eid'][0]['value']);
			}
		}

		$output->addAttribute('subType', $subType);

		$output->addChild('peerReviewed', $peerReviewed);

		$output->addChild('publicationCategory', 'research');

		$publicationStatusesElement = $output->addChild('publicationStatuses');

		$publicationStatusElement = $publicationStatusesElement->addChild('publicationStatus');

		$publicationStatusElement->addChild('statusType', 'published');

		$dateParts = explode('-', $input['dc.date.issued'][0]['value']);

		$dateElement = $publicationStatusElement->addChild('date');

		$dateElement->addChild('year', $dateParts[0], $v3NameSpace);

		if(!empty($dateParts[1]))
		{
			$dateElement->addChild('month', $dateParts[1], $v3NameSpace);
		}
		if(!empty($dateParts[2]))
		{
			$dateElement->addChild('day', $dateParts[2], $v3NameSpace);
		}

		$output->addChild('workflow', 'approved');

		$output->addChild('language', 'en_US');

		$titleElement = $output->addChild('title');

		$textElement = $titleElement->addChild('text', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.title'][0]['value']), ENT_NOQUOTES)), $v3NameSpace);

		$textElement->addAttribute('lang', "en");

		$textElement->addAttribute('country', "US");

		if(!empty($input['dc.description.abstract'][0]['value']))
		{
			$abstractElement = $output->addChild('abstract');

			$textElement = $abstractElement->addChild('text', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.description.abstract'][0]['value']), ENT_NOQUOTES)), $v3NameSpace);

			$textElement->addAttribute('lang', "en");

			$textElement->addAttribute('country', "US");
		}

		$allAuthorsDeptIDs = array();

		$personsElement = $output->addChild('persons');

		foreach($input['dc.contributor.author'] as $authorKey => $author)
		{
			if(is_int($authorKey))
			{
				if(!empty($author['children']['dc.identifier.orcid'][0]['value']))
				{
					$match = checkPerson(array("orcid" => $author['children']['dc.identifier.orcid'][0]['value']));
				}

				if(isset($author['children']['dc.identifier.scopusid'][0]['value']))
				{
					$scopusID = $author['children']['dc.identifier.scopusid'][0]['value'];
				}
				else
				{
					$scopusID = '';
				}

				$authorElement = $personsElement->addChild('author');

				$authorElement->addChild('role', 'author');

				$personElement = $authorElement->addChild('person');

				if(!empty($scopusID))
				{
					$personElement->addAttribute('id', $scopusID);
				}

				if($scopusID === $authorID)
				{
					$personElement->addAttribute('origin', "internal");
				}
				elseif(in_array($scopusID, $localPersonsScopusIDs))
				{
					$match = checkPerson(array("name" => $author['value']));

					if(!empty($match['localID']))
					{
						$personElement->addAttribute('origin', "internal");
					}
					else
					{
						$personElement->addAttribute('origin', "unknown");
					}
				}
				else
				{
					$personElement->addAttribute('origin', "external");
				}

				$nameParts = explodeName($author['value']);

				$personElement->addChild('fullName', $nameParts['fullName']);
				$personElement->addChild('firstName', $nameParts['firstName']);
				$personElement->addChild('lastName', $nameParts['lastName']);

				if(!empty($author['children']['dc.contributor.affiliation'][0]['value']))
				{
					$organisationsElement = $authorElement->addChild('organisations');

					foreach($author['children']['dc.contributor.affiliation'] as $affKey => $aff)
					{
						//echo print_r($aff).PHP_EOL;

						if(is_int($affKey)&&!empty($aff['value']))
						{
							$localAff = FALSE;

							$organisationElement = $organisationsElement->addChild('organisation');

							if(institutionNameInString($aff['value']))
							{
								$localAff = TRUE;
							}

							if(!empty($aff['children']['dc.identifier.scopusid'][0]['value']))
							{
								$scopusOrgID = $aff['children']['dc.identifier.scopusid'][0]['value'];

								if($scopusOrgID === '60092945')
								{
									$localAff = TRUE;
								}
								else
								{
									$pureMatch = checkForPureExternalOrgId($scopusOrgID, 'scopusid', 0);

									if(!empty($pureMatch['scivalID']))
									{
										$organisationElement->addAttribute('id', $pureMatch['scivalID']);
									}
									else
									{
										$organisationElement->addAttribute('id', $scopusOrgID);
									}
								}
							}

							if($localAff)
							{
								$match = checkPerson(array("name" => $author['value']));

								if(!empty($match['localID']))
								{
									//$report .= print_r($match, TRUE).PHP_EOL;

									$thisAuthorDeptIds = checkDeptIDs($match['localID'], $input['dc.date.issued'][0]['value']);

									//$report .= print_r($thisAuthorDeptIds, TRUE).PHP_EOL;

									if(!empty($thisAuthorDeptIds))
									{
										$allAuthorsDeptIDs = array_merge($allAuthorsDeptIDs, $thisAuthorDeptIds);

										//Attribute may have already been set...
										if(!isset($organisationElement['id']))
										{
											$organisationElement->addAttribute('id', array_shift($thisAuthorDeptIds));
										}
										else
										{
											$organisationElement['id'] = array_shift($thisAuthorDeptIds);
										}
									}
									else
									{
										//add highest level KAUST org id
										$organisationElement->addAttribute('id', '30000085');
									}
								}
								else
								{
									$personElement['origin'] = "unknown";

									//add highest level KAUST org id if attribute has not been set
									//Attribute may have already been set as an external org based on the Scopus ID...
									if(!isset($organisationElement['id']))
									{
										$organisationElement->addAttribute('id', '30000085');
									}
								}
							}

							$nameElement = $organisationElement->addChild('name');

							$textElement = $nameElement->addChild('text', htmlspecialchars($aff['value'], ENT_QUOTES), $v3NameSpace);

							$textElement->addAttribute('lang', "en");

							$textElement->addAttribute('country', "US");
						}
					}

					//Add entries for remaining departments
					if(!empty($thisAuthorDeptIds))
					{
						foreach($thisAuthorDeptIds as $deptID)
						{
							$organisationElement = $organisationsElement->addChild('organisation');

							$organisationElement->addAttribute('id', $deptID);
						}
					}
				}
				elseif(!empty($handle))
				{
					$kaustPersons = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'kaust.person'), array('value'), 'arrayOfValues');

					if(in_array($author['value'], $kaustPersons))
					{
						$match = checkPerson(array("name" => $author['value']));

						$organisationsElement = $authorElement->addChild('organisations');
						
						$organisationElement = $organisationsElement->addChild('organisation');

						if(!empty($match['localID']))
						{
							//$report .= print_r($match, TRUE).PHP_EOL;

							$thisAuthorDeptIds = checkDeptIDs($match['localID'], $input['dc.date.issued'][0]['value']);

							//$report .= print_r($thisAuthorDeptIds, TRUE).PHP_EOL;

							if(!empty($thisAuthorDeptIds))
							{
								$allAuthorsDeptIDs = array_merge($allAuthorsDeptIDs, $thisAuthorDeptIds);

								//Attribute may have already been set...
								if(!isset($organisationElement['id']))
								{
									$organisationElement->addAttribute('id', array_shift($thisAuthorDeptIds));
								}
								else
								{
									$organisationElement['id'] = array_shift($thisAuthorDeptIds);
								}
							}
							else
							{
								//add highest level KAUST org id
								$organisationElement->addAttribute('id', '30000085');
							}
						}
						else
						{
							$personElement['origin'] = "unknown";

							//add highest level KAUST org id if attribute has not been set
							//Attribute may have already been set as an external org based on the Scopus ID...
							if(!isset($organisationElement['id']))
							{
								$organisationElement->addAttribute('id', '30000085');
							}
						}

						//Add entries for remaining departments
						if(!empty($thisAuthorDeptIds))
						{
							foreach($thisAuthorDeptIds as $deptID)
							{
								$organisationElement = $organisationsElement->addChild('organisation');

								$organisationElement->addAttribute('id', $deptID);
							}
						}
					}
				}

				//Add pure person id if there was a local match whether based on scopus author id, orcid id, or on existence of a local affiliation
				if(!empty($match['localID']))
				{
					//$report .= print_r($match, TRUE).PHP_EOL;
					$personElement['origin'] = "internal";

					//Set student number as PureID for individuals who have both a personnel number and a student number
					if(!empty($match['studentNumber']))
					{
						$pureid = $match['studentNumber'];
					}
					elseif(!empty($match['personnelNumber']))
					{
						$pureid = $match['personnelNumber'];
					}

					if(!empty($pureid))
					{
						if(isset($personElement['id']))
						{
							$personElement['id'] = $pureid;
						}
						else
						{
							$personElement->addAttribute('id', $pureid);
						}
					}
				}
			}
			unset($match);
			unset($thisAuthorDeptIds);
		}

		if(empty($ownerID))
		{
			$ownerID = owner($allAuthorsDeptIDs);
		}

		$ownerElement = $output->addChild('owner');

		$ownerElement->addAttribute('id', $ownerID);

		if(!empty($input['dc.identifier.issn'][0]['value']))
		{
			$issn = str_replace('-', '', $input['dc.identifier.issn'][0]['value']);

			$asjcresult = $irts->query("
			SELECT ASJC
			FROM repositoryAuthorityControl.ScopusJournalList
			WHERE PrintISSN LIKE '$issn'
			OR EISSN LIKE '$issn'");

			while ($asjcrow = $asjcresult->fetch_row())
			{
				$asjcs = $asjcrow[0];
			}

			if(!empty($asjcs))
			{
				$asjcs = str_replace(';', '', $asjcs);
				$asjcs = explode(' ', $asjcs);

				$keywordsElement = $output->addChild('keywords');

				$logicalGroupElement = $keywordsElement->addChild('logicalGroup', NULL, $v3NameSpace);

				$logicalGroupElement->addAttribute('logicalName', "ASJCSubjectAreas");

				$structuredKeywordsElement = $logicalGroupElement->addChild('structuredKeywords', NULL, $v3NameSpace);

				foreach($asjcs as $asjc)
				{
					if(substr($asjc, 2, 2)!== '00')
					{
						$asjc = substr($asjc, 0, 2) . '00/' . $asjc;
					}

					$structuredKeyword = $structuredKeywordsElement->addChild('structuredKeyword', NULL, $v3NameSpace);

					$structuredKeyword->addAttribute('classification', "/dk/atira/pure/subjectarea/asjc/$asjc");
				}
			}
		}

		$urlsElement = $output->addChild('urls');

		if(!empty($handle))
		{
			$url = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.relation.url'), array('value'), 'singleValue');
		}

		if(empty($url)&&!empty($input['dc.identifier.doi'][0]['value']))
		{
			$url = getRedirectURLByDOI($input['dc.identifier.doi'][0]['value']);
		}

		if(!empty($handleURL))
		{
			$urlElement = $urlsElement->addChild('url');
			$urlElement->addChild('url', htmlspecialchars($handleURL, ENT_QUOTES));

			$descriptionElement = $urlElement->addChild('description');

			$textElement = $descriptionElement->addChild('text', 'KAUST Repository', $v3NameSpace);

			$textElement->addAttribute('lang', "en");

			$textElement->addAttribute('country', "US");
		}

		if(!empty($url))
		{
			$urlElement = $urlsElement->addChild('url');
			$urlElement->addChild('url', htmlspecialchars($url, ENT_QUOTES));

			$descriptionElement = $urlElement->addChild('description');

			$textElement = $descriptionElement->addChild('text', 'Publisher Link', $v3NameSpace);

			$textElement->addAttribute('lang', "en");

			$textElement->addAttribute('country', "US");
		}

		if(!empty($handle))
		{
			$fileurls = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dspace.bitstream.url'), array('rowID', 'value'), 'arrayOfValues');
		}

		if(!empty($fileurls[0]['value'])||!empty($input['dc.identifier.doi'][0]['value']))
		{
			$electronicVersionsElement = $output->addChild('electronicVersions');

			foreach($input['dc.identifier.doi'] as $doi)
			{
				$electronicVersionDOIElement = $electronicVersionsElement->addChild('electronicVersionDOI');
				$electronicVersionDOIElement->addChild('doi', $doi['value']);
			}

			//For the time being we will only send information about the first file, other files are either supplemental data files or ETD admin forms. For the supplemental data we may go through a process to distinguish them as parts or related items with their own record. For the ETD forms they are internal only and may be moved to an admin bundle at some point that hides them more thoroughly from public view.

			if(!empty($fileurls[0]['value']))
			{
				$fileurl = $fileurls[0]['value'];
				$fileRowID = $fileurls[0]['rowID'];

				$electronicVersionLinkElement = $electronicVersionsElement->addChild('electronicVersionLink');

				$version = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.eprint.version'), array('value'), 'singleValue');

				if($version === 'Publisher\'s Version/PDF')
				{
					$version = 'publishersversion';
				}
				elseif($version === 'Post-print')
				{
					$version = 'authorsversion';
				}
				elseif($version === 'Pre-print')
				{
					$version = 'preprint';
				}

				if(!empty($version))
				{
					$purexml .= '<v1:version>'.$version.'</v1:version>';
				}

				//Check if file has restricted access
				$fileembargo = getValues($irts, setSourceMetadataQuery('repository', $handle, $fileRowID, 'dspace.bitstream.embargo'), array('value'), 'singleValue');

				$fileRestricted = getValues($irts, setSourceMetadataQuery('repository', $handle, $fileRowID, 'dspace.bitstream.accessRights'), array('value'), 'singleValue');

				if(!empty($fileembargo))
				{
					$fileembargo = DateTime::createFromFormat('Y-m-d', $fileembargo);

					$electronicVersionLinkElement->addChild('publicAccess', 'embargoed');

					$electronicVersionLinkElement->addChild('embargoEndDate', $fileembargo->format('d-m-Y'));
				}
				elseif(!empty($fileRestricted))
				{
					$electronicVersionLinkElement->addChild('publicAccess', 'closed');
				}
				else
				{
					$electronicVersionLinkElement->addChild('publicAccess', 'open');
				}

				$electronicVersionLinkElement->addChild('link', $fileurl);
			}
		}

		if(!empty($input['dc.identifier.eid'][0]['value']))
		{
			$urlElement = $urlsElement->addChild('url');
			$urlElement->addChild('url', htmlspecialchars('http://www.scopus.com/inward/record.url?scp='.str_replace('2-s2.0-','',$input['dc.identifier.eid'][0]['value']).'&partnerID=8YFLogxK', ENT_QUOTES));

			$descriptionElement = $urlElement->addChild('description');

			$textElement = $descriptionElement->addChild('text', 'Scopus Record Link', $v3NameSpace);

			$textElement->addAttribute('lang', "en");

			$textElement->addAttribute('country', "US");
		}

		$bibliographicalNotesElement = $output->addChild('bibliographicalNotes');

		$bibliographicalNoteElement = $bibliographicalNotesElement->addChild('bibliographicalNote');

		$textElement = $bibliographicalNoteElement->addChild('text', '{bibliographicalNotePlaceholder}', $v3NameSpace);

		$textElement->addAttribute('lang', "en");

		$textElement->addAttribute('country', "US");

		if(!empty($handle))
		{
			$localGrantNumbers = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'kaust.grant.number'), array('value'));
			if(!empty($localGrantNumbers))
			{
				$bibliographicalNoteElement = $bibliographicalNotesElement->addChild('bibliographicalNote');

				$textElement = $bibliographicalNoteElement->addChild('text', 'Acknowledged KAUST grant number(s): '.implode(', ', $localGrantNumbers), $v3NameSpace);

				$textElement->addAttribute('lang', "en");

				$textElement->addAttribute('country', "US");
			}

			$acknowledgement = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.description.sponsorship'), array('value'), 'singleValue');
			if(!empty($acknowledgement))
			{
				$bibliographicalNoteElement = $bibliographicalNotesElement->addChild('bibliographicalNote');

				$textElement = $bibliographicalNoteElement->addChild('text', 'Acknowledgements: '.str_replace('&', '&amp;', htmlspecialchars(strip_tags($acknowledgement), ENT_NOQUOTES)), $v3NameSpace);

				$textElement->addAttribute('lang', "en");

				$textElement->addAttribute('country', "US");
			}
			$collections = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dspace.collection.handle'), array('value'));
			if(in_array('10754/581392', $collections))
			{
				$bibliographicalNoteElement = $bibliographicalNotesElement->addChild('bibliographicalNote');

				$textElement = $bibliographicalNoteElement->addChild('text', 'This publication acknowledges KAUST support, but has no KAUST affiliated authors.', $v3NameSpace);

				$textElement->addAttribute('lang', "en");

				$textElement->addAttribute('country', "US");
			}
		}

		$visibilityElement = $output->addChild('visibility', 'Public');

		$externalIdsElement = $output->addChild('externalIds');

		$externalIDFields = array('researchoutputwizard'=>'dc.identifier.uri','PubMedCentral'=>'dc.identifier.pmcid','PubMed'=>'dc.identifier.pmid','Arxiv'=>'dc.identifier.arxivid');

		foreach($externalIDFields as $externalIDLabel => $externalIDField)
		{
			if(!empty($input[$externalIDField][0]['value']))
			{
				$idElement = $externalIdsElement->addChild('id', $input[$externalIDField][0]['value']);

				$idElement->addAttribute('type', $externalIDLabel);
			}
		}

		if(isset($input['dc.identifier.eid'][0]['value']))
		{
			$idElement = $externalIdsElement->addChild('id', str_replace('2-s2.0-','',$input['dc.identifier.eid'][0]['value']));

			$idElement->addAttribute('type', "Scopus");

			if(empty($input['dc.identifier.uri'][0]['value']))
			{
				$idElement = $externalIdsElement->addChild('id', $input['dc.identifier.eid'][0]['value']);

				$idElement->addAttribute('type', "researchoutputwizard");

				$idElement = $externalIdsElement->addChild('id', str_replace('2-s2.0-','',$input['dc.identifier.eid'][0]['value']));

				$idElement->addAttribute('type', "QABO");
			}
		}

		if(isset($input['dc.identifier.wosut'][0]['value']))
		{
			$idElement = $externalIdsElement->addChild('id', str_replace('WOS:','',$input['dc.identifier.wosut'][0]['value']));

			$idElement->addAttribute('type', "WOS");
		}

		if(!empty($input['dc.identifier.pages'][0]['value']))
		{
			$pages = $input['dc.identifier.pages'][0]['value'];

			if($type !== 'book')
			{
				$output->addChild('pages', $pages);
			}

			if(strpos($pages, '-') !== FALSE)
			{
				$pages = explode('-', $pages);

				if(is_numeric($pages[0])&&is_numeric($pages[1]))
				{
					$pageCount = $pages[1]-$pages[0]+1;

					if($pageCount !== 0)
					{
						$output->addChild('numberOfPages', $pageCount);
					}
				}
			}
		}

		if($subType === 'article')
		{
			if(!empty($input['dc.identifier.issue'][0]['value']))
			{
				$output->addChild('journalNumber', $input['dc.identifier.issue'][0]['value']);
			}

			if(!empty($input['dc.identifier.volume'][0]['value']))
			{
				$output->addChild('journalVolume', $input['dc.identifier.volume'][0]['value']);
			}

			if(!empty($input['dc.identifier.journal'][0]['value']))
			{
				$journalElement = $output->addChild('journal');

				$journalElement->addChild('title', htmlspecialchars($input['dc.identifier.journal'][0]['value'], ENT_QUOTES));

				if(!empty($issn))
				{
					$printIssnsElement = $journalElement->addChild('printIssns');

					if(strpos($issn, '-') === FALSE)
					{
						$issn = substr($issn, 0, 4).'-'.substr($issn, -4);
					}

					$printIssnsElement->addChild('issn', $issn);
				}
			}
		}
		elseif(in_array($type, array('book', 'chapterInBook')))
		{
			if(!empty($input['dc.identifier.isbn'][0]['value']))
			{
				$printIsbnsElement = $output->addChild('printIsbns');

				$printIsbnsElement->addChild('isbn', $input['dc.identifier.isbn'][0]['value']);
			}

			if($type === 'chapterInBook')
			{
				if(!empty($handle))
				{
					$input['dc.identifier.journal'][0]['value'] = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.journal'), array('value'), 'singleValue');
				}

				if(!empty($doi['value']) && empty($input['dc.identifier.journal'][0]['value']))
				{
					$input['dc.identifier.journal'][0]['value'] = getValues($irts, setSourceMetadataQuery('crossref', $doi['value'], NULL, 'dc.identifier.journal'), array('value'), 'singleValue');
				}

				if(!empty($input['dc.identifier.journal'][0]['value']))
				{
					$hostPublicationTitleElement = $output->addChild('hostPublicationTitle', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.identifier.journal'][0]['value']))));
				}
				elseif(!empty($input['dc.conference.name'][0]['value']))
				{
					$hostPublicationTitleElement = $output->addChild('hostPublicationTitle', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.conference.name'][0]['value']))));
				}
			}

			if(!empty($input['dc.publisher'][0]['value']))
			{
				$publisherElement = $output->addChild('publisher');

				$publisherElement->addChild('name', str_replace('&', '&amp;', htmlspecialchars(strip_tags($input['dc.publisher'][0]['value']))));
			}
		}

		return array('purexml'=>$purexml,'output'=>$output);
	}
