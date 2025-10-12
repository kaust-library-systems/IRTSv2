<?php
	//Define function to update repository record dc.date.issued fields to match the appropriate crossref date, or to add additional dates as needed
	function autoUpdateDates($report, $errors, $recordTypeCounts)
	{
		global $irts;

		if(isset($_GET['year'])) //option to check for missing dates in a specific year
		{
			$year = $_GET['year'];

			$items = getValues($irts, "SELECT DISTINCT r.idInSource handle, r.value doi, s.idInSource scopusEID FROM `metadata` r LEFT JOIN metadata s USING(value)
				WHERE r.source = 'repository'
				AND r.`field` LIKE 'dc.identifier.doi'
				AND r.`place` LIKE 0
				AND r.deleted IS NULL
				AND s.source = 'scopus'
				AND s.`field` LIKE 'dc.identifier.doi'
				AND s.deleted IS NULL
			AND NOT EXISTS (
				SELECT value FROM metadata
				WHERE source = 'repository'
				AND idInSource = r.idInSource
				AND field IN('dc.date.issued','dc.date.published-print','dc.date.published-online','dc.date.posted')
				AND value LIKE '$year%'
				AND deleted IS NULL
			)
			AND EXISTS (
				SELECT value FROM metadata
				WHERE source IN('crossref','scopus')
				AND idInSource = s.idInSource
				AND field IN('crossref.date.published-online','crossref.date.published-print','dc.date.issued')
				AND value LIKE '$year%'
				AND deleted IS NULL
			)
			ORDER BY handle DESC", array('handle','doi','scopusEID'), 'arrayOfValues');
		}
		else //otherwise check for dates added since last update
		{
			//set fromDate
			if(isset($_GET['fromDate']))
			{
				$fromDate = $_GET['fromDate'];
			}
			else
			{
				//date of last update
				$fromDate = getValues(
					$irts, 
					"SELECT `timestamp` FROM messages WHERE process = 'autoUpdateDates' ORDER BY `timestamp` DESC LIMIT 1",
					array('timestamp'),
					'singleValue'
				);
			}

			//get items with dates added since last update
			$items = getValues($irts, "SELECT DISTINCT r.idInSource handle, r.value doi, s.idInSource scopusEID FROM `metadata` r LEFT JOIN metadata s USING(value)
				WHERE r.source = 'repository'
				AND r.`field` LIKE 'dc.identifier.doi'
				AND r.`place` LIKE 0
				AND r.deleted IS NULL
				AND s.source = 'scopus'
				AND s.`field` LIKE 'dc.identifier.doi'
				AND s.deleted IS NULL
				AND EXISTS (
					SELECT value FROM metadata
					WHERE source IN('crossref','scopus')
					AND idInSource = s.idInSource
					AND field IN('crossref.date.published-online','crossref.date.published-print','dc.date.issued')
					AND added > '$fromDate'
					AND deleted IS NULL
				)",
				array('handle','doi','scopusEID'),
				'arrayOfValues'
			);
		}

		foreach($items as $item)
		{
			$itemReport = '';

			$flagged = FALSE;

			$changed = FALSE;

			$recordTypeCounts['all']++;

			$handle = $item['handle'];
			$doi = $item['doi'];

			$itemReport .= PHP_EOL.$recordTypeCounts['all'].') '.$handle.PHP_EOL.'-- '.$doi.PHP_EOL;

			//For Scopus
			$scopusEID = $item['scopusEID'];

			$itemReport .= '-- Scopus EID: '.$scopusEID.PHP_EOL;

			$newDates = array();
			$printDates = array();

			$issueDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'repository' AND idInSource = '$handle' AND field = 'dc.date.issued' AND deleted IS NULL", array('value'), 'singleValue');

			$onlineDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'crossref' AND idInSource = '$doi' AND field = 'crossref.date.published-online' AND deleted IS NULL", array('value'), 'singleValue');

			if(!empty($onlineDate))
			{
				if($issueDate !== $onlineDate)
				{
					$itemReport .= '-- Old dc.date.issued = '.$issueDate.PHP_EOL;
					$newDates['dc.date.issued'][] = $onlineDate;
					$itemReport .= '-- New dc.date.issued = '.$onlineDate.PHP_EOL;
				}

				$newDates['dc.date.published-online'][] = $onlineDate;
				$flagged = TRUE;
			}
			elseif(empty($onlineDate))
			{
				$itemReport .= '-- Old dc.date.issued = '.$issueDate.PHP_EOL;
				$createdDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'crossref' AND idInSource = '$doi' AND field = 'crossref.date.created' AND deleted IS NULL", array('value'), 'singleValue');
				$itemReport .= '-- No online publication date, using Crossref DOI created date'.PHP_EOL;
				$newDates['dc.date.issued'][] = $createdDate;
				$newDates['dc.date.published-online'][] = $createdDate;
				$flagged = TRUE;
			}

			$crossrefPrintDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'crossref' AND idInSource = '$doi' AND field = 'crossref.date.published-print' AND deleted IS NULL", array('value'), 'singleValue');

			if(!empty($crossrefPrintDate))
			{
				$printDates[] = $crossrefPrintDate;

				$itemReport .= '-- dc.date.published-print from Crossref: '.$crossrefPrintDate.PHP_EOL;
			}

			$scopusPrintDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'scopus' AND idInSource = '$scopusEID' AND field = 'dc.date.issued' AND deleted IS NULL", array('value'), 'singleValue');

			//if scopus print date is set to -01-01, set to the year only
			if(!empty($scopusPrintDate))
			{
				if(strpos($scopusPrintDate, '-01-01') !== FALSE)
				{
					$scopusPrintDate = substr($scopusPrintDate, 0, 4);

					$itemReport .= '-- Scopus print date set to year only: '.$scopusPrintDate.PHP_EOL;
				}

				if($scopusPrintDate !== $issueDate)
				{
					$printDates[] = $scopusPrintDate;

					$itemReport .= '-- dc.date.published-print from Scopus: '.$scopusPrintDate.PHP_EOL;
				}
			}

			if(count($printDates) > 0)
			{
				$newDates['dc.date.published-print'] = array_unique($printDates);
			}

			$arxivID = getValues($irts, "SELECT value FROM metadata WHERE source = 'repository' AND idInSource = '$handle' AND field = 'dc.identifier.arxivid' AND deleted IS NULL", array('value'), 'singleValue');

			if(!empty($arxivID))
			{
				$itemReport .= '-- arxiv ID: '.$arxivID.PHP_EOL;

				$postedDate = getValues($irts, "SELECT value FROM metadata WHERE source = 'arxiv' AND idInSource = '$arxivID' AND field = 'dc.date.issued' AND deleted IS NULL", array('value'), 'singleValue');
				
				if(!empty($postedDate))
				{
					$itemReport .= '-- Preprint posted date from arXiv = '.$postedDate.PHP_EOL;
					$newDates['dc.date.posted'][] = $postedDate;
					$flagged = TRUE;
				}
			}

			if($flagged)
			{
				$response = dspacePrepareAndApplyPatchToItem($handle, $newDates, __FUNCTION__);

				$recordTypeCounts[$response['status']]++;

				$itemReport .= $response['report'];

				$itemReport .= '-- '.$response['status'].PHP_EOL;

				$errors = array_merge($errors, $response['errors']);
			}
			else
			{
				$recordTypeCounts['unchanged']++;
			}
			
			$report .= $itemReport;

			echo $itemReport;
		}

		return array('recordTypeCounts' => $recordTypeCounts, 'report' => $report, 'errors' => $errors);
	}
