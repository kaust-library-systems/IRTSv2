<?php
	//Define function to send notices to scientific illustrators of new publications acknowledging them
	function sendNoticeToIllustrators($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$recordTypeCounts['matched'] = 0;
		$recordTypeCounts['unmatched'] = 0;
		$recordTypeCounts['sent'] = 0;

		$matches = array();

		//all items so far in 2020
		$items = getValues($irts, "SELECT idInSource handle, value acknowledgement FROM `metadata`
			WHERE `source` LIKE 'repository'
			AND `field` LIKE 'dc.description.sponsorship'
			AND `value` LIKE '%illustrator%'
			AND `added` >= '2020-01-01'
			AND deleted IS NULL
			AND CONCAT('repository_',idInSource) NOT IN (
				SELECT idInSource FROM metadata
				WHERE source = 'irts'
				AND field = 'irts.date.notificationSentToIllustrator'
				AND deleted IS NULL)", array('handle','acknowledgement'), 'arrayOfValues');

		foreach(ILLUSTRATORS as $name => $email)
		{
			$report .= 'Illustrator: '.$name.PHP_EOL;

			$nameParts = explode(' ', $name);

			$matchCount = 0;

			$table = '<table width="400">
				<tr>
				<th align="center" style="border:1px solid #333;border-collapse: collapse;">Repository Link</th>
				<th align="center" style="border:1px solid #333;border-collapse: collapse;">Citation</th>
				<th align="center" style="border:1px solid #333;border-collapse: collapse;">Acknowledgement</th>
				</tr>';

			foreach($items as $item)
			{
				$recordTypeCounts['all']++;

				$handle = $item['handle'];

				$acknowledgement = $item['acknowledgement'];

				$match = FALSE;

				foreach($nameParts as $namePart)
				{
					if(strpos($acknowledgement, $namePart) !== false)
					{
						$match = TRUE;
					}
				}

				if($match)
				{
					$report .= '- matched handle: '.$handle.PHP_EOL;

					$matches[] = $handle;

					$matchCount++;

					$citation = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.citation'), array('value'), 'singleValue');

					if(empty($citation))
					{
						$citation = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.title'), array('value'), 'singleValue');
					}

					$table .= '<tr>
						<td align="center" style="border:1px solid #333;border-collapse: collapse;"><a href="http://hdl.handle.net/'.$handle.'">http://hdl.handle.net/'.$handle.'</a></td>
						<td align="left" style="border:1px solid #333;border-collapse: collapse;">'.$citation.'</td>
						<td align="left" style="border:1px solid #333;border-collapse: collapse;">'.$acknowledgement.'</td>
						</tr>';

					$irts_ID = 'repository_'.$handle;

					$sent = saveValue('irts', $irts_ID, 'irts.date.notificationSentToIllustrator', 1, date('Y-m-d'), NULL);
				}
			}

			$table .= '</table>';

			if($matchCount > 0)
			{
				$to = $email;
				$subject = "New acknowledgement in the KAUST repository";
				$message = "Dear ".$nameParts[0].",<p></p>"."You have been acknowledged in a new publication in the KAUST repository, see details below:<p></p>".$table."<p></p>"."<p></p>"."Please let us know if this information is useful to you, or if changes are needed."."<p></p>"."Regards, Rawan"."<p></p>"."On behalf of the KAUST Repository Team";

				// Always set content-type when sending HTML email
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

				//CC repository email
				$headers .= "From: " .IR_EMAIL. "\r\n";
				$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";
				mail($to,$subject,$message,$headers);
				$recordTypeCounts['sent']++;
			}
		}

		$unmatched = array();

		$matches = array_unique($matches);

		foreach($items as $item)
		{
			if(!in_array($item['handle'],$matches))
			{
				$unmatched[$item['handle']] = $item['acknowledgement'];
			}
		}

		$recordTypeCounts['matched'] = count($matches);
		$recordTypeCounts['unmatched'] = count($unmatched);

		$report .= 'Unmatched Acknowledgements: '.print_r($unmatched, TRUE).PHP_EOL;

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
