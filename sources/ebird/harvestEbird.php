<?php
	//Define function to harvest repository metadata via DSpace REST API
	function harvestEbird($source)
	{
		global $irts, $newInProcess, $errors;

		$token = loginToDSpaceRESTAPI();

		$report = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new checklist'=>0,'new observation'=>0,'new image'=>0,'unchanged'=>0,'skipped'=>0);

		$locations = array('L8133092','L5728118','L8016632','L8079976');

		foreach($locations as $location)
		{
			$options = array(
			  CURLOPT_URL => "https://ebird.org/ws2.0/product/lists/".$location,
			  CURLOPT_FOLLOWLOCATION => false,
			  CURLOPT_CUSTOMREQUEST => "GET",
			  CURLOPT_HTTPHEADER => array(
				"X-eBirdApiToken: k3mgojbsf70o"
			  ),
			);

			$response = makeCurlRequest($options);

			$result = json_decode($response, true);

			foreach($result as $checklist)
			{
				$checklistID = $checklist['subId'];
				//$checklistID = 'S54161445';


				//check for existing entry
				$check = select($irts, "SELECT * FROM sourceData WHERE source LIKE ? AND idInSource LIKE ? AND deleted IS NULL", array('ebird', $checklistID));

				//if not existing
				if(mysqli_num_rows($check) === 0)
				{
					echo 'Checklist ID '.$checklistID.' is new!'.PHP_EOL;

					$options = array(
					  CURLOPT_URL => "https://ebird.org/ws2.0/product/checklist/view/".$checklistID,
					  CURLOPT_FOLLOWLOCATION => false,
					  CURLOPT_CUSTOMREQUEST => "GET",
					  CURLOPT_HTTPHEADER => array(
						"X-eBirdApiToken: k3mgojbsf70o"
					  ),
					);

					$response = makeCurlRequest($options);

					$recordType = processEbirdRecord($token, $checklistID, $response, $report, $recordTypeCounts);

					//break 2;
					$recordTypeCounts[$recordType]++;
				}
				else
				{
					echo 'Checklist ID '.$checklistID.' is old.'.PHP_EOL;
				}

				unset($checklist['subId']);
				$checklistArray[] = $checklistID;

				ob_flush();
			}
		}
		$summary = saveReport($source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
