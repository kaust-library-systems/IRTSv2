<?php

/*

**** This file is responsible for updating MySQL tables with data for reuse in PowerBI dashboards.

** Parameters :
	mode: whether to append or replace the data in the tables
	from: the date from which to start updating the tables (only used if mode is append)
	exports: a comma-separated list of exports to run (default is all)
	
** Return:
	None 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function updateRepositoryDashboardDataTables($report, $errors, $exportCounts)
{	
	global $irts;
	
	// accepted modes are 'update' and 'replaceAll'
	if(isset($_GET['mode'])){
		$mode = $_GET['mode'];
	}
	else{
		$mode = 'update'; //default mode
	}

	$report .= 'Mode: '.$mode.PHP_EOL;

	$from = NULL; // if mode is update, default action is for from date to be set based on the last entry in each table

	// accept manual parameter indicating which date to start updating the tables from
	if($mode == 'update' && isset($_GET['from'])){		
		$from = $_GET['from'];
	}

	// exports to run
	if(isset($_GET['exports'])){
		$exports = explode(',', $_GET['exports']);
	}
	else{
		$exports = [
			'authors',
			'departments',
			'dois',
			'downloads',
			'files',
			'items',
			'itemFilters',
			'itemsToCollections',
			'itemsToCommunities',
			'itemsToDepartments',
			'metadataReviewStatus',
			'metadataSourceRecords',
			'orcids',
			'pageViews',
			'publishers',
			'publisherAgreements'
		];
	}
	
	foreach($exports as $export){
		$exportCounts['all']++;
		
		$result = call_user_func(($export), $mode, $from);

		$exportReport = 'Exporting '.$export.'...'.$result['report'].PHP_EOL;

		$exportRecordTypeCounts = $result['recordTypeCounts'];

		$exportErrors = $result['errors'];

		$errors = array_merge($errors, $exportErrors);

		$exportSummary = saveReport($irts, $export, $exportReport, $exportRecordTypeCounts, $exportErrors);

		$report .= $exportSummary.PHP_EOL;
	}
	
	echo $report;

	$summary = saveReport($irts, __FUNCTION__, $report, $exportCounts, $errors);
	return array('changedCount'=>$exportCounts['all']-$exportCounts['unchanged'],'summary'=>$summary);		
}