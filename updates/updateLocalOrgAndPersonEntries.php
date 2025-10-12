<?php
	//Define function to update Pure person and org XML on the public server
	function updateLocalOrgAndPersonEntries($report, $errors, $recordTypeCounts)
	{
		global $irts, $report, $errors;
		
		if(isset($_GET['reprocess'])) // possible values: 'orgs', 'persons', 'both', 'neither'
		{
			$reprocess = $_GET['reprocess'];
		}
		else
		{
			$reprocess = 'neither';
		}
		
		$changedCount = 0;

		/* //retrieve org data, if it was added/updated today
		$json = getValues($irts, "SELECT sourceData FROM `sourceData` 
			WHERE `source` = 'local'  
			AND `idInSource` LIKE 'orgs' 
			AND `format` = 'JSON'
			AND `deleted` IS NULL 
			AND `added` LIKE '".TODAY."%'
			ORDER BY `added` DESC 
			LIMIT 1", array('sourceData'), 'singleValue'); */

		$json = getValues($irts, "SELECT sourceData FROM `sourceData` 
			WHERE `source` = 'local'  
			AND `idInSource` LIKE 'orgs' 
			AND `format` = 'JSON'
			AND `deleted` IS NULL 
			ORDER BY `added` DESC 
			LIMIT 1", array('sourceData'), 'singleValue');
		
		if(!empty($json))
		{
			$orgs = json_decode($json, TRUE);
			
			//$orgs = array_slice($orgs, 0, 20);
			
			$result = processLocalOrgs($orgs, $reprocess);
			
			$report .= $result['summary'].PHP_EOL;
			
			$changedCount = $changedCount + $result['changedCount'];
		}
				
		/* $json = getValues($irts, "SELECT sourceData FROM `sourceData` 
			WHERE `source` = 'local'  
			AND `idInSource` LIKE 'persons' 
			AND `format` = 'JSON'
			AND `deleted` IS NULL 
			AND `added` LIKE '".TODAY."%'
			ORDER BY `added` DESC 
			LIMIT 1", array('sourceData'), 'singleValue'); */

		$json = getValues($irts, "SELECT sourceData FROM `sourceData` 
			WHERE `source` = 'local'  
			AND `idInSource` LIKE 'persons' 
			AND `format` = 'JSON'
			AND `deleted` IS NULL 
			ORDER BY `added` DESC 
			LIMIT 1", array('sourceData'), 'singleValue');
		
		if(!empty($json))
		{
			$persons = json_decode($json, TRUE);
			
			//$persons = array_slice($persons, 150, 10);
			
			$result = processLocalPersons($persons, $reprocess); 
			
			$report .= $result['summary'].PHP_EOL;
			
			$changedCount = $changedCount + $result['changedCount'];
		}		
		
		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $report;

		return array('changedCount'=>$changedCount,'summary'=>$summary);
	}
