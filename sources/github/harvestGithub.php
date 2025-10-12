<?php

/*

**** This file responsible for harvesting the metadata for github repositories from github.

** Parameters :
	none

*/

//--------------------------------------------------------------------------------------------


function harvestGithub($source)
{
	global $irts, $newInProcess, $errors;

	$report = '';
	
	$errors = array();
	
	$replaceFromURL = array('.git', 'URL:');

	//Record count variable
	$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0,'no result'=>0);
	
	$githubIDsToCheck = array();

	if(isset($_GET['githubURL']))
	{
		$githubURLs = array($_GET["githubURL"]);
	}
	else
	{
		// get all the github URLs from the database 
		$githubURLs = getValues($irts, "SELECT DISTINCT value FROM `metadata` 
			WHERE `source` IN ('irts','repository')
			AND `field` = 'dc.relation.issupplementedby' 
			AND `value` LIKE 'URL:https://github.com/%' 
			AND `deleted` IS NULL", array('value'), 'arrayOfValues');
	}
	
	if(isset($_GET['ignoreExisting']))
	{
		$existingGithubIDs = array();
	}
	else
	{
		// get list of github IDs that have already been harvested 
		$existingGithubIDs =  getValues($irts, "SELECT DISTINCT idInSource FROM `sourceData` 
			WHERE `source` = 'github' 
			AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
	}

	foreach($githubURLs as $githubURL)
	{
		$report .= '  - '.$githubURL.PHP_EOL;
		
		// Get the data from github using github URL
		$githubURL = str_replace($replaceFromURL, '', $githubURL);
		$githubID = str_replace('https://github.com/', '', $githubURL);
		
		$lastCharacter = substr($githubID, -1);
		
		if(in_array($lastCharacter, array('.','/')))
		{
			$githubID = substr($githubID, 0, -1);
		}
		
		if(!in_array($githubID, $existingGithubIDs))
		{
			$githubIDsToCheck[] = $githubID;
		}
	}
	
	$githubIDsToCheck = array_unique($githubIDsToCheck);
	
	$report .= count($githubIDsToCheck).' Github IDs to check: '.PHP_EOL;

	$recordTypeCounts['all'] = count($githubIDsToCheck);
	
	if(count($githubIDsToCheck) > 30)
	{
		$report .= ' Limiting to first 30 IDs to avoid running into API limits.'.PHP_EOL;
		
		$githubIDsToCheck = array_slice($githubIDsToCheck, 0, 30);
	}
	
	foreach($githubIDsToCheck as $githubID)
	{
		$input = array();

		$report .= '  - '.$githubID.PHP_EOL;
		
		echo $githubID.PHP_EOL;

		sleep(10);
		// get the github repository data
		$jsonInfo = queryGithub($githubID);

		if(is_string($jsonInfo))
		{
			$jsonInfoArray = json_decode($jsonInfo, true);
			// save the two json in one variable
			$input = $jsonInfoArray;

			// get the readme file 
			sleep(10);
			$readme = queryGithubGetReadME($githubID, true);

			if(is_string($readme))
			{
				$readmeArray = json_decode($readme, true);
				$input['readme'] = $readmeArray;
			}

			//Save the json file in the source 
			$json = json_encode($input, JSON_PRETTY_PRINT);
			$result = saveSourceData($irts, $source, $githubID, $json, 'JSON');
			$recordType = $result['recordType'];

			$recordTypeCounts[$recordType]++;
			$output = processGithubRecord($input);

			// save
			$functionReport = saveValues($source, $githubID, $output, NULL);
			$result = saveValue($source, $githubID, 'dc.type', 1, 'Software', NULL);
			
			$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;
			
			//check for existing entries and add to IRTS as new entry if none found
			$result = addToProcess($source, $githubID, 'dc.identifier.github', FALSE, 'Harvested based on GitHub URL mentioned in related article data availability statement');

			if($result['status'] === 'inProcess')
			{
				$newInProcess++;
			}

			$report .= '- IRTS status: '.$result['status'].PHP_EOL;
		}
		else
		{
			print_r($jsonInfo);
			$recordTypeCounts['no result']++;
		}

		ob_flush();
		flush();
		set_time_limit(0);
	}

	$summary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);
	
	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
}