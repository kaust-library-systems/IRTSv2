<?php
/*

**** This file is responsible for standardizing the downloads information.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function downloads($mode, $from){	

	global $ga, $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'UA Rows' => 0, 
		'UA Rows Added' => 0,
		'GA4 Rows' => 0,
		'GA4 Rows Added' => 0,
		'GA4 Rows Skipped' => 0
	];

	$baseURL = REPOSITORY_BASE_URL.'/handle/';
	
	//totalEvents is the number of downloads
	//base UA query
	$baseUaQuery = "
	FROM UA
	WHERE sourceSystem = 'Repository'
	AND totalEvents IS NOT NULL
	AND totalEvents > 0
	AND (pageUrl LIKE '/bitstream%'
		OR `pageUrl` LIKE '10754%'
		OR pageUrl LIKE '/kaust/bitstream%'
	)";

	//base GA4 query
	$baseGa4Query = "
		FROM `GA4` 
		WHERE `sourceSystem` = 'Repository'
		AND `totalEvents` > 0";

	if($mode == 'replaceAll'){
		//delete all rows from downloads table
		$result = $repository->query("TRUNCATE TABLE downloads");
	}
	else{
		if($from !== NULL){
			//delete rows greater than or equal to the from date
			$result = $repository->query("DELETE FROM downloads WHERE `Year and Month as DateTime` >= '$from'");

			//Create a date object using the from date
			$fromDate = new DateTime($from);
		}
		else{
			$lastDate = getValues($repository, "SELECT MAX(`Year and Month as DateTime`) AS lastDate FROM downloads", array('lastDate'), 'singleValue');

			//Create a date object using the last date
			$fromDate = new DateTime($lastDate);

			//add 1 month to the last date (to avoid reharvesting the last month)
			$fromDate->add(new DateInterval('P1M'));
		}

		//get the month and year of the from date
		$fromMonth = date('m', strtotime($fromDate->format('Y-m-d')));

		$fromYear = date('Y', strtotime($fromDate->format('Y-m-d')));

		//add month and year to check in UA query for update mode
		$baseUaQuery .= " AND (year > '$fromYear' OR (year >= '$fromYear' AND month >= '$fromMonth'))";

		//add month and year to check in GA4 query for update mode
		$baseGa4Query .= " AND (year > '$fromYear' OR (year >= '$fromYear' AND month >= '$fromMonth'))";
	}

	//UA count query
	$totalUARows = getValues($ga, "SELECT COUNT(*) AS totalRows 
		".$baseUaQuery, array('totalRows'), 'singleValue');

	while($recordTypeCounts['UA Rows']+1 < $totalUARows){
		// get the statistics 1,000 rows at a time
		$statistics = getValues($ga, "SELECT *
			".$baseUaQuery."
			ORDER BY year, month ASC
			LIMIT ".$recordTypeCounts['UA Rows'].", 1000", array('pageUrl', 'referrer', 'country', 'year', 'month', 'totalEvents'));

		$recordTypeCounts['all'] += count($statistics);

		foreach ($statistics as $statisticsRow){
	
			// UA downloads should always have a handle in the pageUrl
			if(strpos($statisticsRow['pageUrl'], '10754/') !== FALSE){

				$recordTypeCounts['UA Rows']++;

				$row = prepareStatisticsRow('UA', 'downloads', $statisticsRow);

				//insert row in downloads table
				if(addRow('downloads', $row)){
					$recordTypeCounts['UA Rows Added']++;
				}
			}
		}
	}

	//GA4 count query
	$totalGA4Rows = getValues($ga, "SELECT COUNT(*) AS totalRows
		".$baseGa4Query, array('totalRows'), 'singleValue');

	while($recordTypeCounts['GA4 Rows']+1 < $totalGA4Rows){
		// get the statistics 1,000 rows at a time
		$statistics = getValues($ga, "SELECT * 
			".$baseGa4Query."
			ORDER BY year, month ASC
			LIMIT ".$recordTypeCounts['GA4 Rows'].", 1000", array('pageUrl', 'referrer', 'country', 'year', 'month', 'totalEvents'));

		$recordTypeCounts['all'] += count($statistics);

		foreach ($statistics as $statisticsRow){

			$recordTypeCounts['GA4 Rows']++;
			
			$row = prepareStatisticsRow('GA4', 'downloads', $statisticsRow);

			//downloads without handles are ignored (either the URL is not for a file or the file is not in the ORIGINAL bundle)
			if(!empty($row['Handle'])){
				//insert row in downloads table
				if(addRow('downloads', $row)){
					$recordTypeCounts['GA4 Rows Added']++;
				}
			}
			else{
				$recordTypeCounts['GA4 Rows Skipped']++;
			}
		}
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}