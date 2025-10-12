#!/usr/bin/php-cgi

<?php
	ini_set('display_errors', 1);

	set_include_path('/var/www/irts');

	//include core configuration and common function files
	include_once 'include.php';

	//Create harvest summary to send
	$harvestSummary = '';

	$newInProcess = 0;
	$totalChanged = 0;

	if(isset($_GET['source']))
	{
		$sources = explode(',', $_GET["source"]);
	}

	//standard options are 'reprocess' (reprocess metadata already saved in the sourceData table without querying the source), 'reharvest' (reharvest metadata for known items from the source based on previously harvested ids), 'requery' (iterate through full requery), and 'new' (query most recent items only)
	if(isset($_GET['harvestType']))
	{
		$harvestType = $_GET['harvestType'];
	}
	else
	{
		$harvestType = 'new';
	}

	if($harvestType === 'reprocess')
	{
		foreach($sources as $source)
		{
			$harvestSummary .= ' - '.$source.' reprocessed';
			
			$sourceReport = '';

			if(isset($_GET['idInSource']))
			{
				$idInSource = $_GET['idInSource'];

				$harvestSummary .= ' - '.$idInSource.' reprocessed';

				$result = $irts->query("SELECT `rowID` FROM `sourceData` WHERE `source` LIKE '$source' AND `idInSource` LIKE '$idInSource' AND `deleted` IS NULL");
			}
			else
			{
				$result = $irts->query("SELECT `rowID` FROM `sourceData` WHERE `source` LIKE '$source' AND `deleted` IS NULL");
			}

			if($result->num_rows!==0)
			{
				while($row = $result->fetch_assoc())
				{
					set_time_limit(0);

					$rowID = $row['rowID'];

					$sourceDataResult = $irts->query("SELECT `idInSource`, `sourceData`, `format` FROM `sourceData` WHERE `rowID` = '$rowID'");

					if($sourceDataResult->num_rows!==0)
					{
						while($sourceDataRow = $sourceDataResult->fetch_assoc())
						{
							$idInSource = $sourceDataRow['idInSource'];

							$sourceData = $sourceDataRow['sourceData'];

							$format = $sourceDataRow['format'];
							
							//echo $format;

							if($format === 'JSON')
							{
								$sourceData = json_decode($sourceData, TRUE);
							}
							elseif($format === 'XML')
							{
								$sourceData = simplexml_load_string($sourceData);
							}

							$record = call_user_func('process'.(ucfirst($source)).'Record', $sourceData);
							
							$functionReport = saveValues($source, $idInSource, $record, NULL);
						}
					}
				}
			}
		}
	}
	else
	{
		foreach($sources as $source)
		{
			$sourceHarvestTime = microtime(true);
			
			set_time_limit(0);

			$results = call_user_func_array('harvest'.(ucfirst($source)), array($source, $harvestType));
			
			$totalChanged += $results['changedCount'];

			$harvestSummary .= PHP_EOL.$results['summary'];
			
			$sourceHarvestTime = microtime(true)-$sourceHarvestTime;
			
			insert($irts, 'messages', array('process', 'type', 'message'), array('sourceHarvestTime', 'report', $source.' harvest time: '.$sourceHarvestTime.' seconds'));
		}
	}

	//Complete harvest message to send
	$harvestSummary = 'Harvest Report'.PHP_EOL.' - New items needing review: '.$newInProcess.PHP_EOL.' - Total changed records: '.$totalChanged.PHP_EOL.$harvestSummary;

	if($totalChanged !== 0)
	{
		//Settings for harvest report email
		$to = IR_EMAIL;
		$subject = "Results of Publications Harvest";

		$headers = "From: " .IR_EMAIL. "\r\n";

		//Send
		mail($to,$subject,$harvestSummary,$headers);
	}
	
	echo $harvestSummary;
?>
