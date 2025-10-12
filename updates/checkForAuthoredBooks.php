<?php
	//Define function to check for if book chapter records are part of an authored book
	function checkForAuthoredBooks($report, $errors, $recordTypeCounts) {
		global $irts;

		$isbns = [];
		$authoredBookISBNs = [];
		$editedBookISBNs = [];

		$recordTypeCounts['skipped'] = 0;
		$recordTypeCounts['merged'] = 0;
		$recordTypeCounts['chaptersInEditedBooks'] = 0;
		
		$bookChaptersInProcess = getValues(
			$irts, 
			"SELECT *  FROM `metadata` WHERE `source` LIKE 'irts' AND `field` LIKE 'dc.identifier.doi' AND `deleted` IS NULL
				AND idInSource IN (SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'irts' AND `field` LIKE 'dc.type' AND `value` LIKE 'Book Chapter' AND `deleted` IS NULL)
				AND idInSource IN (SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'irts' AND `field` LIKE 'irts.status' AND `value` LIKE 'inProcess' AND `deleted` IS NULL)
				AND idInSource NOT IN (SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'irts' AND `field` LIKE 'dc.relation.ispartof' AND `value` LIKE 'DOI:%' AND `deleted` IS NULL)",
			array('idInSource', 'value'),
			'arrayOfValues'
		);

		//only check a few for testing
		//$bookChaptersInProcess = array_slice($bookChaptersInProcess, 0, 5);

		foreach($bookChaptersInProcess as $bookChapter)
		{
			$itemReport = '';
			
			$recordTypeCounts['all']++;
			
			$idInIRTS = $bookChapter['idInSource'];
			$doi = $bookChapter['value'];

			$itemReport .= $recordTypeCounts['all'].') Checking for authored book for chapter DOI: '.$doi.PHP_EOL;

			$isbn = getValues($irts, setSourceMetadataQuery('crossref', $doi, NULL, 'dc.identifier.isbn'), array('value'), 'singleValue');

			if(!empty($isbn))
			{
				if(!in_array($isbn, $isbns))
				{
					$isbns[] = $isbn;

					$book = queryCrossref('isbn', $isbn, 'book');

					if(empty($book))
					{
						$book = queryCrossref('isbn', $isbn, 'monograph');
					}

					if(empty($book))
					{
						$book = queryCrossref('isbn', $isbn, 'edited-book');
					}

					if(empty($book))
					{
						$book = queryCrossref('isbn', $isbn, 'reference-book');
					}

					if(!empty($book))
					{
						$itemReport .= '- Book found for ISBN: '.$isbn.PHP_EOL;

						$book = $book[0];
						
						$itemReport .= '- Book DOI: '.$book['DOI'].PHP_EOL;

						//this saves citation and bibtex of the book under the DOI source so that it can easily be retrieved later based on the book DOI in the chapter record's dc.relation.ispartof field
						if(identifyRegistrationAgencyForDOI($book['DOI'], $report)==='crossref')
						{
							$itemReport .= '- Book DOI is registered with Crossref - citation and bibtex saved.'.PHP_EOL;
						}

						if(in_array($book['DOI'], array('10.1515/9783111028163', '10.1002/9781394201532'))) //handle books where professor is lone editor and also coauthor on each chapter...
						{
							$harvestBasis = 'Authored book found based on book chapter';
							
							$itemReport .= '- Treat book as authored book...'.PHP_EOL;
							
							$sourceData = retrieveCrossrefMetadataByDOI($book['DOI'], $report);

							$result = processCrossrefRecord($sourceData);

							$recordType = $result['recordType'];
							
							$itemReport .= ' - '.$recordType.PHP_EOL;

							$recordTypeCounts[$recordType]++;
							
							$result = addToProcess('crossref', $book['DOI'], 'dc.identifier.doi', FALSE, $harvestBasis);

							if($result['status'] === 'inProcess')
							{
								$recordTypeCounts['new']++;
							}

							$itemReport .= '- IRTS status: '.$result['status'].PHP_EOL;

							$authoredBookISBNs[$isbn] = $book['DOI'];

							$recordTypeCounts['merged']++;

							$result = saveValue('irts', $idInIRTS, 'irts.status', 1, 'completed' , NULL);

							$itemReport .= '- Book chapter record marked as complete'.PHP_EOL;

							$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$authoredBookISBNs[$isbn], NULL);

							$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
						}
						elseif(isset($book['author']))
						{
							$itemReport .= '- Book author(s): '.json_encode($book['author']).PHP_EOL;

							$bookAuthorCount = count($book['author']);

							$chapterAuthors = getValues($irts, setSourceMetadataQuery('crossref', $doi, NULL, 'dc.contributor.author'), array('value'), 'arrayOfValues');

							$itemReport .= '- Chapter author(s): '.json_encode($chapterAuthors).PHP_EOL;

							$chapterAuthorCount = count($chapterAuthors);

							if($chapterAuthorCount === $bookAuthorCount)
							{
								//check if first author family name matches
								$bookFirstAuthorFamilyName = $book['author'][0]['family'];

								$chapterFirstAuthorFamilyName = explode(', ', $chapterAuthors[0])[0];

								if($bookFirstAuthorFamilyName === $chapterFirstAuthorFamilyName)
								{
									$itemReport .= '- First author family name ('.$chapterFirstAuthorFamilyName.') matches'.PHP_EOL;

									//check if last author family name matches
									$bookLastAuthorFamilyName = $book['author'][$bookAuthorCount-1]['family'];

									$chapterLastAuthorFamilyName = explode(', ', $chapterAuthors[$chapterAuthorCount-1])[0];

									if($bookLastAuthorFamilyName === $chapterLastAuthorFamilyName)
									{
										$itemReport .= '- Last author family name ('.$chapterLastAuthorFamilyName.') matches'.PHP_EOL;

										$harvestBasis = 'Authored book found based on book chapter';

										$sourceData = retrieveCrossrefMetadataByDOI($book['DOI'], $report);

										$result = processCrossrefRecord($sourceData);

										$recordType = $result['recordType'];
										
										$itemReport .= ' - '.$recordType.PHP_EOL;

										$recordTypeCounts[$recordType]++;
										
										$result = addToProcess('crossref', $book['DOI'], 'dc.identifier.doi', FALSE, $harvestBasis);

										if($result['status'] === 'inProcess')
										{
											$recordTypeCounts['new']++;
										}

										$itemReport .= '- IRTS status: '.$result['status'].PHP_EOL;

										$authoredBookISBNs[$isbn] = $book['DOI'];

										$recordTypeCounts['merged']++;

										$result = saveValue('irts', $idInIRTS, 'irts.status', 1, 'completed' , NULL);

										$itemReport .= '- Book chapter record marked as complete'.PHP_EOL;

										$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$authoredBookISBNs[$isbn], NULL);

										$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
									}
									else
									{
										$itemReport .= '- Last author family name mismatch'.PHP_EOL;

										$editedBookISBNs[$isbn] = $book['DOI'];

										$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$book['DOI'], NULL);

										$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;

										$recordTypeCounts['chaptersInEditedBooks']++;
									}
								}
								else
								{
									$itemReport .= '- First author family name mismatch'.PHP_EOL;

									$editedBookISBNs[$isbn] = $book['DOI'];

									$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$book['DOI'], NULL);

									$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;

									$recordTypeCounts['chaptersInEditedBooks']++;
								}
							}
							else
							{
								$itemReport .= '- Author count mismatch'.PHP_EOL;

								$editedBookISBNs[$isbn] = $book['DOI'];

								$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$book['DOI'], NULL);

								$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;

								$recordTypeCounts['chaptersInEditedBooks']++;
							}
						}
						elseif(isset($book['editor']))
						{
							$itemReport .= '- Book editor(s): '.json_encode($book['editor']).PHP_EOL;

							$editedBookISBNs[$isbn] = $book['DOI'];

							$recordTypeCounts['chaptersInEditedBooks']++;

							$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$editedBookISBNs[$isbn], NULL);

							$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
						}
						else
						{
							$itemReport .= '- No author(s) or editor(s) found for book'.PHP_EOL;

							if($book['type'] === 'edited-book')
							{
								$editedBookISBNs[$isbn] = $book['DOI'];

								$recordTypeCounts['chaptersInEditedBooks']++;

								$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$book['DOI'], NULL);

								$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
							}
							else
							{
								$recordTypeCounts['skipped']++;
							}
						}

						//$itemReport .= '- Book query result: '.json_encode($book).PHP_EOL;
					}
					else
					{
						$recordTypeCounts['skipped']++;

						$itemReport .= '- No book found for ISBN: '.$isbn.PHP_EOL;
					}
				}
				else
				{
					$itemReport .= '- ISBN: '.$isbn.' already checked'.PHP_EOL;

					if(in_array($isbn, array_keys($authoredBookISBNs)))
					{
						$recordTypeCounts['merged']++;

						$result = saveValue('irts', $idInIRTS, 'irts.status', 1, 'completed' , NULL);

						$itemReport .= '- Book chapter record marked as complete'.PHP_EOL;

						$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$authoredBookISBNs[$isbn], NULL);

						$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
					}
					elseif(in_array($isbn, array_keys($editedBookISBNs)))
					{
						$recordTypeCounts['chaptersInEditedBooks']++;

						$itemReport .= '- Book chapter in edited book'.PHP_EOL;

						$result = saveValue('irts', $idInIRTS, 'dc.relation.ispartof', 1, 'DOI:'.$editedBookISBNs[$isbn], NULL);

						$itemReport .= '- IsPartOf relation added to IRTS record'.PHP_EOL;
					}
					else
					{
						$recordTypeCounts['skipped']++;
					}
				}
			}
			else
			{
				$itemReport .= '- No ISBN found for chapter DOI: '.$doi.PHP_EOL;
				
				$recordTypeCounts['skipped']++;
			}

			echo $itemReport;

			$report .= $itemReport;
		}

		//echo $report;

		$summary = saveReport($irts,__FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
