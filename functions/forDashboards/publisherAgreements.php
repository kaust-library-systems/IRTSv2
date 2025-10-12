<?php
/*

**** This file is responsible for getting the data for kaust publishers agreements.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	-- parameters are accepted but not used in this function as the table is small and updates quickly
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function publisherAgreements($mode, $from)
{	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'publisherAgreements' => 0
	];
	
	//delete all rows (always replace all data, regardless of mode)
	$result = $repository->query("TRUNCATE TABLE publisherAgreements");	

	// get the publisher id 
	$publishers = getValues($irts, "SELECT idInSource, value FROM `metadata` WHERE `source` = 'PA' AND `field` = 'pa.publisher'  AND `deleted` IS NULL", array( 'idInSource', 'value'), 'arrayOfValues');
	
	foreach($publishers as $publisher)
	{
		$agreements = getPublisherAgreements($publisher['idInSource']);
		
		foreach($agreements as $agreement)
		{
			$recordTypeCounts['all']++;

			$row = array();

			$splitID = explode('-', $publisher['idInSource']);
			$publisherID  = $splitID[0];
			$publisherName = $publisher['value'];
			$eligibleAuthors = $agreement['pa.eligibleauthors'];
			$type = $agreement['pa.type'];
			$startDate = $agreement['pa.date.start'];
			$endDate = $agreement['pa.date.end'];

			// if endDate is greater than TODAY, then the agreement is active
			if(strtotime($endDate) > strtotime(date('Y-m-d')))
			{
				$active = 'Yes';
			}
			else
			{
				$active = 'No';
			}

			$row = array('Publisher ID' => $publisherID,
						 'Publisher Name' => $publisherName,
						 'Agreement Type' => $type,
						 'Eligible Authors' => $eligibleAuthors,
						 'Start Date' => $startDate,
						 'End Date' => $endDate,
						 'Active' => $active);

			// insert the row into the table
			if(addRow('publisherAgreements', $row))
			{
				$recordTypeCounts['publisherAgreements']++;
			}
		}
	}
	
	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}