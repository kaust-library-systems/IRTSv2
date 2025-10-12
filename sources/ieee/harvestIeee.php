<?php
	//Define function to harvest IEEE metadata records
	function harvestIeee($source)
	{
		global $irts, $newInProcess, $errors;

		$report = '';

		$errors = array();

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0);

		$iterationsOfIeeeUpdate = 1;

		$resultPlace = 1;

		//Change this to decide how many of the available updates to run
		while($iterationsOfIeeeUpdate <= 2)
		{
			$harvestBasis = 'Harvested based on affiliation search';
			
			//daily harvest just gets the most recent 200 items for each iteration of the search, sorted in descending order by last update date
			$url = IEEE_API.'apikey='.IEEE_API_KEY.'&format=json&max_records=200&start_record='.$resultPlace.'&sort_order=desc&sort_field=insert_date&affiliation=';

			if($iterationsOfIeeeUpdate === 1)
			{
				$url .= INSTITUTION_ABBREVIATION;
			}
			elseif($iterationsOfIeeeUpdate === 2)
			{
				$url .= urlencode(INSTITUTION_NAME);
			}

			$results = json_decode(file_get_contents($url), TRUE);

			$total = $results['total_records'];

			foreach($results['articles'] as $sourceData)
			{
				$resultPlace++;
				$recordTypeCounts['all']++;

				//print_r($sourceData);

				$recordType = processIeeeRecord($sourceData);

				$recordTypeCounts[$recordType]++;
				
				$articleNumber = $sourceData['article_number'];
				
				$report .= $recordTypeCounts['all'].') '.$articleNumber.PHP_EOL;

				$report .= ' - '.$source.' status: '.$recordType.PHP_EOL;
				
				$result = addToProcess('ieee', $articleNumber, 'ieee.article_number', TRUE, $harvestBasis);
				
				if($result['status'] === 'inProcess')
				{
					$newInProcess++;
				}

				$report .= '- IRTS status: '.$result['status'].PHP_EOL;
			}

			$iterationsOfIeeeUpdate++;
		}

		$sourceSummary = saveReport($irts, $source, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
