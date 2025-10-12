<?php
/*

**** This file is responsible for listing known Crossref publishers.

** Parameters :
	$mode : whether to update or replace the data in the table
	$from : the date from which to start updating the table
	-- parameters are accepted but not used in this function as the table is small and updates quickly
	
** Return:
	$report, $recordTypeCounts, $errors

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function publishers($mode, $from)
{	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'publishers Rows Added' => 0
	];

	//delete all rows (always replace all data, regardless of mode)
	$result = $repository->query("TRUNCATE TABLE publishers");	
	
	// get the publisher ids
	$publishers = getValues($irts, "SELECT idInSource, value FROM `metadata` WHERE `source` = 'crossref' AND `field` = 'crossref.member.name' AND `deleted` IS NULL", array('idInSource', 'value'), 'arrayOfValues');
	
	$publisherNames = array();

	foreach($publishers as $publisher)
	{
		$recordTypeCounts['all']++;
		
		$row = array();
		
		$splitID = explode('_', $publisher['idInSource']);
		$publisherID  = $splitID[1];
		$row['Publisher ID'] = $publisherID;

		$publisherName = $publisher['value'];
		$row['Publisher Name'] = $publisherName;

		$hasActiveAgreement = FALSE;
		
		$agreements = getPublisherAgreements($publisherID);
		
		foreach($agreements as $agreement)
		{
			$endDate = $agreement['pa.date.end'];
			
			// if endDate is greater than TODAY, then the agreement is active
			if(strtotime($endDate) > strtotime(date('Y-m-d')))
			{
				$hasActiveAgreement = TRUE;
				break;
			}
		}

		// if hasActiveAgreement is TRUE
		if($hasActiveAgreement)
		{
			$active = 'Yes';
		}
		else
		{
			$active = 'No';
		}

		$row['Has Active Agreement'] = $active;

		//publishers with two Crossref member IDs under the same name will only be added once
		if(!in_array($publisherName, $publisherNames))
		{
			$publisherNames[] = $publisherName;

			//insert row in publishers table
			if(addRow('publishers', $row)){
				$recordTypeCounts['publishers Rows Added']++;
			}
		}
		else
		{
			$report .= 'Duplicate publisher name: '.$publisherName;
		}		
	}
	
	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}