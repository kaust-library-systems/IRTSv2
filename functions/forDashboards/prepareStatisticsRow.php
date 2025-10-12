<?php
/*

**** This function prepares statistics rows for entry.

** Parameters :
	$source : the source of the data (UA or GA4)
	$statisticType : the type of statistic (pageViews or downloads)
	$statisticsRow : the row of statistics data
	
** Return:
	$row : array of column names and cleaned values for the row

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function prepareStatisticsRow($source, $statisticType, $statisticsRow){
	
	global $irts;

	$row = array();

	$pageURL = $statisticsRow['pageUrl'];

	$row['Page URL'] = $pageURL;
	
	//download specific
	if($statisticType == 'downloads'){

		$row['Downloads'] = $statisticsRow['totalEvents'];
		
		//UA data
		if($source == 'UA'){
			//no referrer information was saved prior to 2018, referrer is NULL for all records prior to 2018, but should exclude bot traffic
			if($statisticsRow['year'] < 2018){
				$row['Visitor Type'] = 'Human';

				$row['Referrer String'] = '';

				$row['Referrer Name'] = 'Unknown';
			}
		}
		
		if(!isset($row['Visitor Type'])){
			if(empty($statisticsRow['referrer']) || $statisticsRow['referrer'] == '(direct)'){
				$row['Visitor Type'] = 'Suspected Bot or Crawler';

				$row['Referrer String'] = $statisticsRow['referrer'];

				$row['Referrer Name'] = 'None';
			} else{
				$row['Visitor Type'] = 'Human';

				$row['Referrer String'] = $statisticsRow['referrer'];

				$row['Referrer Name'] = assignReferrerName($statisticsRow['referrer']);
			}
		}
	}

	//pageViews specific
	if($statisticType == 'pageViews'){

		$row['Page Views'] = $statisticsRow['pageviews'];

		//in the past all pages were prefixed with /kaust/
		if($pageURL == '/' || $pageURL == '/kaust/'){
			$row['Page Type'] = 'home';
		}
		elseif(strpos($pageURL, '/kaust/') === 0){
			$row['Page Type'] = explode('/', $pageURL)[2];
		}
		else{
			$row['Page Type'] = explode('/', $pageURL)[1];
		}

		if(strpos($row['Page Type'], '?') !== FALSE){
			$row['Page Type'] = explode('?', $row['Page Type'])[0];
		}

		$row['Referrer String'] = $statisticsRow['referrer'];

		$row['Referrer Name'] = assignReferrerName($statisticsRow['referrer']);

		if($row['Referrer Name'] == 'Unknown'){
			$row['Known Referrer'] = 'No';
		}
		else{
			$row['Known Referrer'] = 'Yes';
		}
	}

	//UA pageURLs may contain item handles
	if($source == 'UA'){
		if(strpos($pageURL, '10754/') !== FALSE){
			// get the handle from the pageUrl
			$row['Handle'] = '10754/'.substr(explode('10754/', $pageURL)[1], 0, 6);
		}
		else{
			// no handle
			$row['Handle'] = '';
		}
	}

	//for GA4 pageURLs should contain item or bitstream UUIDs that can be used to get the corresponding item handle
	if($source == 'GA4'){
		$matches = array();

		$uuidRegex = '/\b[0-9a-f]{8}\b-[0-9a-f]{4}\b-[0-9a-f]{4}\b-[0-9a-f]{4}\b-[0-9a-f]{12}\b/i';

		preg_match_all($uuidRegex, $pageURL, $matches);

		if(!empty($matches[0])){
			$uuid = $matches[0][0];

			//echo $uuid.PHP_EOL;
			
			// get the handle based on the UUID

			//if the statistic is a download only count if the bitstream is in the ORIGINAL bundle
			if($statisticType == 'downloads'){
				$handle = getValues($irts, "SELECT `idInSource` 
					FROM `metadata` 
					WHERE `source` = 'repository' 
					AND `field` = 'dspace.bitstream.uuid'
					AND `value` LIKE '$uuid'
					AND parentRowID IN (
						SELECT `rowID` FROM `metadata` WHERE `source` LIKE 'repository' 
						AND `field` LIKE 'dspace.bundle.name' 
						AND `value` LIKE 'ORIGINAL'
						AND deleted IS NULL
					)
					AND deleted IS NULL", array('idInSource'), 'singleValue');
			}
			elseif($statisticType == 'pageViews'){
				$handle = getValues($irts, "SELECT `idInSource` 
					FROM `metadata` 
					WHERE `source` = 'repository' 
					AND `field` = 'dspace.uuid'
					AND `value` LIKE '$uuid'
					AND deleted IS NULL", array('idInSource'), 'singleValue');
			}

			//if the handle contains a period, it is a versioned handle and should be truncated, we just want the main handle
			if(strpos($handle, '.') !== FALSE){
				$row['Handle'] = explode('.', $handle)[0];
			}
			else{
				$row['Handle'] = $handle;
			}

			//echo $row['Handle'].PHP_EOL;
		}
		else{
			// no handle
			$row['Handle'] = '';
		}
	}

	//the remaining rows are the same across all statistics
	$row['Country'] = $statisticsRow['country'];

	$row['Year'] = $statisticsRow['year'];

	$row['Month'] = $statisticsRow['month'];

	$yearAndMonth = new DateTime($statisticsRow['year'].'-'.$statisticsRow['month'].'-01');

	$row['Year and Month as DateTime'] = $yearAndMonth->format('Y-m-d H:i:s');

	return $row;
}