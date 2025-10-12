<?php
	//Define function to generate and send reports to faculty of their publications from the last year
	function sendAnnualReportToFaculty($report, $errors, $recordTypeCounts)
	{
		global $irts, $repository;

		$recordTypeCounts = [
			'all'=>0,
			'sent'=>0,
			'skipped - no publications'=>0,
			'errors'=>0
		];

		//list of fields to use for matching the year
		$dateFields = array('dc.date.accepted','dc.date.issued','dc.date.published-online','dc.date.published-print','dc.date.posted','dc.date.submitted');

		//list of roles to use for matching the person
		$roles = ['author','editor'];

		//types covered by the Open Access Policy
		$types = ['Article','Book Chapter', 'Conference Paper', 'Preprint', 'Protocol'];

		if(isset($_GET['limit']))
		{
			$limit = ' LIMIT '.$_GET['limit'];
		}
		else
		{
			$limit = '';
		}

		if(!isset($_GET['mode']))
		{
			echo 'mode must be set to either: sendReportsToFaculty or sendSummaryToRepository';
		}
		else
		{
			$mode = $_GET['mode'];

			if(isset($_GET['year']))
			{
				$year = $_GET['year'];
			}
			else
			{
				$year = date("Y");
			}

			if($mode === 'sendReportsToFaculty')
			{
				//For repository notices to individual faculty
				$sender = INSTITUTION_ABBREVIATION.' Repository';
				$senderEmail = IR_EMAIL;
				$emailSubject = $year.' Publications Summary';
			}
			elseif($mode === 'sendSummaryToRepository')
			{
				//For summary table sent to repository
				$sender = INSTITUTION_ABBREVIATION.' Repository';
				$senderEmail = IR_EMAIL;
				$emailSubject = $year.' Publications Report Summary';
			}

			// Always set content-type when sending HTML email
			$emailHeaders = "MIME-Version: 1.0" . "\r\n";
			$emailHeaders .= "Content-type:text/html;charset=UTF-8" . "\r\n";

			// More headers
			$emailHeaders .= 'From: '.$sender.'<'.$senderEmail.'>' . "\r\n";
			$emailHeaders .= 'Bcc: <'.IR_EMAIL.'>' . "\r\n";

			$facultyTable = '<table width="400">
					  <tr>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">KAUST ID</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Faculty Name</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">ORCID</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Start Date</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Total Items</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Total Publications Covered By OA Policy</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Percent With Full Text</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Number Lacking Full Text</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Total Items Search Link</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Lacking Full Text Search Link</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Total Downloads</th>
						<th align="center" style="border:1px solid #333;border-collapse: collapse;">Problem</th>
					  </tr>';

			if(isset($_GET['kaustid']))
			{
				$currentFacultyIDs = array($_GET['kaustid']);
			}
			else
			{
				$currentFacultyIDs = [];
				
				$facultyIDs = getValues($irts, "SELECT m.idInSource, m.parentRowID FROM `metadata` m
				WHERE `source` LIKE 'local'
				AND field = 'local.employment.type'
				AND value LIKE 'Faculty'
				AND `deleted` IS NULL
				AND parentRowID IN (
					SELECT `parentRowID` FROM metadata
					WHERE source LIKE 'local'
					AND `idInSource` = m.idInSource
					AND field = 'local.person.title'
					AND value NOT LIKE '%Instructional%'
					AND deleted IS NULL
				)
				$limit", array('idInSource', 'parentRowID'), 'arrayOfValues');
				
				foreach($facultyIDs as $faculty)
				{
					$idInSource = $faculty['idInSource'];
					$parentRowID = $faculty['parentRowID'];
					
					$endedThisYearOrBefore = getValues($irts, "SELECT `parentRowID` FROM metadata
						WHERE source LIKE 'local'
						AND `parentRowID` = '$parentRowID'
						AND field = 'local.date.end'
						AND (value < '$year' OR value LIKE '$year%')
						AND deleted IS NULL", array('parentRowID'), 'singleValue');
						
					$startedThisYearOrBefore = getValues($irts, "SELECT `parentRowID` FROM metadata
						WHERE source LIKE 'local'
						AND `parentRowID` = '$parentRowID'
						AND field = 'local.date.start'
						AND (value < '$year' OR value LIKE '$year%')
						AND deleted IS NULL", array('parentRowID'), 'singleValue');
						
					if(empty($endedThisYearOrBefore) && !empty($startedThisYearOrBefore))
					{
						$currentFacultyIDs[] = $idInSource;
					}
				}
			}

			//remove duplicates from array
			$currentFacultyIDs = array_unique($currentFacultyIDs);

			foreach($currentFacultyIDs as $facultyID)
			{
				$emailMessage = '';

				$recordTypeCounts['all']++;

				$personReport = PHP_EOL.$recordTypeCounts['all'].') '.$facultyID.PHP_EOL;

				$problem = '';
				$thisYearItemsCount = 0;
				$oaPolicyItemsCount = 0;
				$fullTextCount = 0;
				$noFullTextCount = 0;
				$fullTextPercent = 0;
				$noFullText = array();
				$typeCounts = array();
				
				//For testing
				if(!isset($_GET['test']))
				{
					$facultyEmail = 'repository@kaust.edu.sa';
				}
				elseif($_GET['test'] == 'TRUE')
				{
					$facultyEmail = 'repository@kaust.edu.sa';
				}
				elseif($_GET['test'] == 'FALSE')
				{
					$facultyEmail = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'local.person.email'), array('value'), 'singleValue');
				}

				$controlName = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'local.person.name'), array('value'), 'singleValue');

				$givenName = explode(' ', explode(', ', $controlName)[1])[0];
				$familyName = explode(', ', $controlName)[0];

				$orcid = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'dc.identifier.orcid'), array('value'), 'singleValue');
				
				$startDate = getValues($irts, "SELECT value FROM `metadata` WHERE `idInSource` LIKE '$facultyID' AND `field` LIKE 'local.date.start' AND `deleted` IS NULL ORDER BY value ASC", array('value'), 'singleValue');
				
				//Set query for list of all items with this author
				$allItemsQueries = setIndividualPublicationListQueries($controlName, $orcid);

				$allItems = getValues($irts, $allItemsQueries['mysqlQuery'], array('idInSource'), 'arrayOfValues');

				//Get download counts for all items
				$sumOfDownloadCounts = getValues(
					$repository, 
					"SELECT SUM(downloads) FROM downloads 
						WHERE Handle IN ('".implode("','", $allItems)."')
						AND `Visitor Type` = 'Human'", 
					array('SUM(downloads)'), 
					'singleValue'
				);

				//Queries for publications for this author for the requested year
				$queries = setIndividualPublicationListQueries($controlName, $orcid, $roles, NULL, NULL, $dateFields, $year);

				$repositorySearchLink = 'https://'.REPOSITORY_BASE_URL.'/search?' . $queries['dspaceQuery'];

				$thisYearItemsQuery = $queries['mysqlQuery'];

				//Queries for publications for this author for the requested year
				$queries = setIndividualPublicationListQueries($controlName, $orcid, ['author'], $types, NULL, $dateFields, $year);
				//$queries = setIndividualPublicationListQueries($controlName, $orcid, ['author'], $types, NULL, ['dc.date.issued'], $year);

				$noFullTextRepositorySearchLink = 'https://'.REPOSITORY_BASE_URL.'/search?' . $queries['dspaceQuery'].'&f.has_content_in_original_bundle=false,equals';

				$oaPolicyItemsQuery = $queries['mysqlQuery'];

				//print_r($oaPolicyItemsQuery);

				$oaPolicyItems = getValues($irts, $oaPolicyItemsQuery, array('idInSource'), 'arrayOfValues');

				$thisYearItems = getValues($irts, $thisYearItemsQuery, array('idInSource'), 'arrayOfValues');

				$thisYearItemsCount = count($thisYearItems);
				$personReport .= '- '.$thisYearItemsCount.' records found'.PHP_EOL;

				if($thisYearItemsCount == 0)
				{
					$personReport .= '- skipping - No publications found for '.$controlName.' for '.$year;

					$recordTypeCounts['skipped - no publications']++;
				}
				else
				{
					foreach($thisYearItems as $itemHandle)
					{
						$type = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.type'), array('value'), 'singleValue');

						if(isset($typeCounts[$type]))
						{
							$typeCounts[$type]++;
						}
						else
						{
							$typeCounts[$type] = 1;
						}
					}

					$oaPolicyItemsCount = count($oaPolicyItems);

					foreach($oaPolicyItems as $itemHandle)
					{
						if(!empty(getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dspace.bundle.name', 'ORIGINAL'), array('rowID'), 'singleValue')))
						{
							$fullTextCount++;
						}
						else
						{
							$noFullText[] = $itemHandle;
						}
					}

					$fullTextPercent = round(($fullTextCount / $oaPolicyItemsCount) * 100);

					$noFullTextCount = $oaPolicyItemsCount-$fullTextCount;
					
					ksort($typeCounts);

					$typeTable = '<table width="400">
					<tr>
					<th align="center" style="border:1px solid #333;border-collapse: collapse;">Publication Type</th>
					<th align="center" style="border:1px solid #333;border-collapse: collapse;">Number of Items</th>
					</tr>';

					foreach($typeCounts as $type => $count)
					{
						$typeTable .= '<tr>
							<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$type.'</td>
							<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$count.'</td>
							</tr>';
					}

					$typeTable .= '</table>';
					
					$emailMessage =
					'
					<html>
					<body>

					<p>Dear '.$givenName.',</p>
					<p></p>
					<p>The University Library actively tracks the publication of KAUST research as part of the KAUST open access policy and in order to provide the university with a reliable source of information for reporting and evaluation. For example, the information is used to pre-populate the publication section in your annual activity report, simplifying the data entry process.</p>
					<p></p>
					<p>
					For '.$year.', so far we have recorded '.$thisYearItemsCount.' publications for you, of the following types:</p>
					<p></p>
					<p>
					'.$typeTable.'</p>
					<p></p>
					<p>Of the '.$oaPolicyItemsCount.' papers covered by the open access policy, '.$fullTextCount.' items ('.$fullTextPercent.'%) had a full text file deposited to the repository.</p>
					<p></p>'."\r\n";

					if($fullTextPercent !== 100)
					{
						if($noFullTextCount !== 0)
						{
							if($noFullTextCount <= 5)
							{
								if($noFullTextCount === 1)
								{
									$emailMessage .= '
									<p>To reach full compliance with the open access policy and increase access to your research, please send us an accepted manuscript version (after peer review, but before final publisher formatting) of the paper for which no file has been deposited, listed below:</p>'."\r\n";
								}
								else
								{
									$emailMessage .= '
									<p>To reach full compliance with the open access policy and increase access to your research, please send us accepted manuscript versions (after peer review, but before final publisher formatting) of the papers for which no files have been deposited, listed below:</p>'."\r\n";
								}

								$emailMessage .= '<ol>';
								foreach($noFullText as $itemHandle)
								{
									$citation = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.identifier.citation'), array('value'), 'singleValue');

									if(empty($citation))
									{										
										if(empty($problem))
										{
											//$problem = 'empty citations in list of items without full text, generating citations from metadata: '.PHP_EOL;
										}
										
										$authors = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.contributor.author'), array('value'), 'arrayOfValues');
										
										$type = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.type'), array('value'), 'singleValue');
										
										$title = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.title'), array('value'), 'singleValue');

										$venue = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.identifier.journal'), array('value'), 'singleValue');

										if(empty($venue))
										{
											$venue = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.conference.name'), array('value'), 'singleValue');
										}

										$handleLink = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.identifier.uri'), array('value'), 'singleValue');

										if(count($authors) > 1)
										{
											$citation = $authors[0].' et al. ('.$year.') '.$title.'. '.$venue.'. '.$handleLink;
										}
										else
										{
											$citation = $authors[0].' ('.$year.') '.$title.'. '.$venue.'.'.$handleLink;
										}

										//$problem .= PHP_EOL.$citation.PHP_EOL;
									}

									$emailMessage .= '<li>'.$citation.'</li>'."\r\n";
								}
								$emailMessage .= '</ol>';
							}
							else
							{
								$emailMessage .= '
								<p>To reach full compliance with the open access policy and increase access to your research, please send us accepted manuscript versions (after peer review, but before final publisher formatting) of the papers for which no files have been deposited (<a href="'.$noFullTextRepositorySearchLink.'">listed here<a/>).</p>'."\r\n";
							}
							
							$emailMessage .= '
									<p></p><p>If you have already requested an exemption from deposit for the listed items, no further action is needed.</p>'."\r\n";
						}
					}

					$emailMessage .= '<p>To review the full list of your recorded publications for '.$year.', please visit <a href="'.$repositorySearchLink.'">the KAUST Repository</a>.</p>
					<p></p>';
					
					//Don't add download count information when the count is low (this is mostly for new faculty who have only a few very recently deposited items)
					if($sumOfDownloadCounts>100)
					{
						$emailMessage .= '<p>
						Repository statistics show that open access copies of your research affiliated to KAUST and in the KAUST repository (from all years) have been downloaded '.number_format($sumOfDownloadCounts).' times.
						</p>';
					}
					
					$emailMessage .= '<p>
					If you would like to add to or correct any information in the repository, please email us at: '.IR_EMAIL.'.
					</p>
					<p>
					Sincerely, </p>
					<p>University Library</p>
					</body>
					</html>
					';
				}

				// if skipped - no publications, emailMessage will be empty
				if($mode === 'sendReportsToFaculty' && !empty($emailMessage))
				{
					if(mail($facultyEmail,$emailSubject,$emailMessage,$emailHeaders))
					{
						$personReport .= '- Email sent for '.$controlName.' to '.$facultyEmail.PHP_EOL;
						$recordTypeCounts['sent']++;
					}
					else
					{
						$personReport .= '- Error! -- Email failed to send to '.$facultyEmail.PHP_EOL;
						$recordTypeCounts['errors']++;
					}
					sleep(15);
				}

				$facultyTable .= '
					  <tr>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$facultyID.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$controlName.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$orcid.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$startDate.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$thisYearItemsCount.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$oaPolicyItemsCount.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$fullTextPercent.'%</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$noFullTextCount.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;"><a href="'.$repositorySearchLink.'">'.$repositorySearchLink.'</a></td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;"><a href="'.$noFullTextRepositorySearchLink.'">'.$noFullTextRepositorySearchLink.'</a></td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$sumOfDownloadCounts.'</td>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;">'.$problem.'</td>
					  </tr>';

				if(!empty($problem))
				{
					$personReport .= '- '.$problem.PHP_EOL;
				}

				echo $personReport;

				$report .= $personReport;

				ob_flush();
				set_time_limit(0);
				//break;
			}

			$facultyTable .= '</table>';

			if($mode === 'sendSummaryToRepository')
			{
				$emailMessage = '
					<html>
					<body>
					'.$facultyTable.'
					</body>
					</html>';

				if(mail(IR_EMAIL,$emailSubject,$facultyTable,$emailHeaders))
				{
					$report .= 'Summary email sent to '.IR_EMAIL;
				}
				else
				{
					$report .= 'Error! -- Email failed to send to '.$senderEmail;
				}

				//$report .= $emailMessage;
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['all'],'summary'=>$summary);
	}
