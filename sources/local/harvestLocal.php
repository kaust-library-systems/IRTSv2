<?php
	//Define function to harvest information about local entities (KAUST departments and people)
	function harvestLocal($source)
	{
		global $irts, $errors;

		$summary = '';

		$errors = array();

		//Records changed
		$changedCount = 0;

		//Check for entityType
		if(isset($_GET['entityType']))
		{
			$entityTypes = array($_GET['entityType']);
		}
		else
		{
			$entityTypes = array('orgs');
			
			//$entityTypes = array('orgs','persons');
		}

		foreach($entityTypes as $entityType)
		{
			$result = call_user_func_array('syncLocal'.(ucfirst($entityType)), array($source));

			$summary .= $result['summary'];

			$changedCount += $result['changedCount'];
		}

		return array('changedCount'=>$changedCount,'summary'=>$summary);
	}
