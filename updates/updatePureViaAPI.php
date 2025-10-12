<?php
	//Define function to update Pure via API
	function updatePureViaAPI($report, $errors, $recordTypeCounts)
	{
		global $irts, $report, $errors;
		
		//synchronizations to run
		$synchronizations = ['syncOrgs'];
		//$synchronizations = ['syncOrgs','syncPersons','syncUsers'];
		//$synchronizations = ['deletePersonsByEmploymentType','syncOrgs','syncPersons','syncUsers'];
		
		//if set as parameter, use that instead
		if(isset($_GET['synchronization']) && in_array($_GET['synchronization'], $synchronizations))
		{
			$synchronizations = [$_GET['synchronization']];
		}		

		foreach($synchronizations as $synchronization)
		{
			$report .= call_user_func_array($synchronization, array());
		}
		
		//Log full process report
		insert($irts, 'messages', array('process', 'type', 'message'), array(__FUNCTION__, 'report', $report));
		
		echo $report;
		
		$summary = '';

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
