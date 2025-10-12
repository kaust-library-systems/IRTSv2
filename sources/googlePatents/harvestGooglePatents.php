<?php
//Define function to harvest Google Patent metadata
function harvestGooglePatents($source)
{
	global $irts, $errors;
	$report = '';
	
	//init
	$token = loginToDSpaceRESTAPI();
	$recordTypeCounts = array('all'=>0,'new'=>0,'updated'=>0,'deleted'=>0,'unchanged'=>0, 'non-KAUST or emprty record' => 0, 'failed' => 0);
	
	// harvest Patents
	$result = getValues($irts, "SELECT
									  `value` patentNumber
									FROM
									  `metadata`
									WHERE
									  `source` = 'repository' AND(
										`field` = 'dc.identifier.patentnumber' OR `field` = 'dc.identifier.applicationnumber'
									  ) AND `value` NOT LIKE '%/%' AND VALUE = 'US 20120323007 A1' AND VALUE NOT IN(
									  SELECT
										SUBSTR(`idInSource`,
										15) AS `idInSource`
									  FROM
										`metadata`
									  WHERE
										`source` = 'irts' AND `field` = 'irts.check.googlePatents' AND `deleted` IS NULL
									) AND `deleted` IS NULL"
									
							, array('patentNumber'), 'arrayOfValues');
	
	$recordTypeCounts['all'] = count($result);



	foreach($result as $patentNumber) {
		
	
	
		$report = processGooglePatentsRecord($patentNumber, $source, $token ,$recordTypeCounts);
	

		//echo $patentNumber;
		sleep(5);
		ob_flush();
		flush();
		set_time_limit(0);
		
		
	}
	

	
	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
	
	
}
