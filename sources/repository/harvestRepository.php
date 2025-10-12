<?php
	//Define function to harvest repository metadata via OAI-PMH, primarily used to retrieve DSpace community and collection handle information for each item
	function harvestRepository($source)
	{
		global $irts, $newInProcess, $errors;

		$fromDate = '';

		$sourceReport = '';

		//Record count variable
		$recordTypeCounts = array('all'=>0,'new'=>0,'modified'=>0,'deleted'=>0,'unchanged'=>0);

		$errors = array();

		//pass empty fromDate parameter to run daily harvest
		if(!isset($_GET['fromDate']))
		{
			$fromDate = YESTERDAY; //OAI-PMH indexing now runs once a day, so this harvest will run for everything modified during the previous day
		}
		else
		{
			$fromDate = $_GET['fromDate'];
		}
		$sourceReport .= 'From Date: '.$fromDate.PHP_EOL;

		//pass resumption token to restart harvest from point of failure
		if(isset($_GET['resumptionToken']))
		{
			$token = $_GET['resumptionToken'];
		}
		else
		{
			$token = '';
		}
		$sourceReport .= 'Resumption token: '.$token.PHP_EOL;		

		if(empty($fromDate))
		{
			$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListIdentifiers&metadataPrefix=oai_dc');
		}
		else
		{
			$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListIdentifiers&metadataPrefix=oai_dc&from='.$fromDate);
		}

		//option to pass total as parameter
		if(isset($_GET['total']))
		{
			$total = $_GET['total'];
		}
		elseif(isset($oai->ListIdentifiers->resumptionToken))
		{
			$total = $oai->ListIdentifiers->resumptionToken['completeListSize'];
		}
		elseif(isset($oai->ListIdentifiers->header))
		{
			//print_r($oai);
			$total = count($oai->ListIdentifiers->header);
		}
		else
		{
			$total = 0;
		}
		unset($oai);

		$sourceReport .= 'Total: '.$total.PHP_EOL;	

		while($recordTypeCounts['all'] < $total)
		{
			if(!empty($token))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListRecords&resumptionToken='.$token.'');
			}
			elseif(empty($fromDate))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListRecords&metadataPrefix=xoai');
			}
			elseif(!empty($fromDate))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListRecords&metadataPrefix=xoai&from='.$fromDate);
			}
			else
			{
				break;
			}
			
			/* if(!empty($token))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListIdentifiers&resumptionToken='.$token.'');
			}
			elseif(empty($fromDate))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListIdentifiers&metadataPrefix=oai_dc');
			}
			elseif(!empty($fromDate))
			{
				$oai = simplexml_load_file(REPOSITORY_OAI_URL.'verb=ListIdentifiers&metadataPrefix=oai_dc&from='.$fromDate);
			}
			else
			{
				break;
			} */

			if(!empty($oai))
			{
				if(isset($oai->ListRecords))
				{
					foreach($oai->ListRecords->record as $item)
					{
				/* if(isset($oai->ListIdentifiers))
				{
					foreach($oai->ListIdentifiers->header as $item)
					{ */
						$recordTypeCounts['all']++;
						if($recordTypeCounts['all'] === $total+1)
						{
							break 2;
						}

						$sourceReport .= 'Number:'.$recordTypeCounts['all'].PHP_EOL;

						//process item
						$result = processRepositoryRecord($item, $sourceReport);

						$sourceReport .= ' - '.$result['recordType'].PHP_EOL;

						$recordTypeCounts[$result['recordType']]++;

						flush();
						set_time_limit(0);
					}
				}
			}

			$token = $oai->ListRecords->resumptionToken;
			//$token = $oai->ListIdentifiers->resumptionToken;
			if(empty($token))
			{
				break;
			}
		}

		$sourceSummary = saveReport($irts, $source, $sourceReport, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$sourceSummary);
	}
