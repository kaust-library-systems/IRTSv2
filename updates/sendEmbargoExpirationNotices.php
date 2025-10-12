<?php
	function sendEmbargoExpirationNotices($report, $errors, $recordTypeCounts)
	{
		global $irts;

		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();
	
		//possible notification types
		$notificationTypes = array(
			'EtdEmbargoExpirationReminder',
			'PosterEmbargoExpirationReminder'
		);
		
		//array setting whether or not to send each type
		$notificationsToSend = [];
		
		$recordTypeCounts['ETD embargo expiration reminder sent to author and advisor'] = 0;

		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		//CC repository email
		$headers .= "From: " .IR_EMAIL. "\r\n";
		$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";

		if(isset($_GET['itemHandle']))
		{
			$report .= 'Item Handle Requested:'.$_GET['itemHandle'].PHP_EOL;
			
			// if request is set to run for an individual handle, then the notificationToSend must also be set
			if(isset($_GET['notificationToSend']))
			{
				$report .= 'Notification Requested:'.$_GET['notificationToSend'].PHP_EOL;
				
				if(in_array($_GET['notificationToSend'], $notificationTypes))
				{				
					$requestedHandles = array($_GET['itemHandle']);
					
					foreach($notificationTypes as $notificationType)
					{					
						if($notificationType === $_GET['notificationToSend'])
						{
							$notificationsToSend[$notificationType] = TRUE;
						}
						else
						{
							$notificationsToSend[$notificationType] = FALSE;
						}
					}
				}
				else
				{
					echo 'notificationToSend value must be one of: '.implode(', ', array_keys($notificationsToSend));
				}
			}
			else
			{
				echo 'notificationToSend value must be set in order to send a notification for a specific item. Acceptable values are:'.implode(', ', array_keys($notificationsToSend));
			}
		}
		else //all notification types will be sent
		{
			foreach($notificationTypes as $notificationType)
			{					
				$notificationsToSend[$notificationType] = TRUE;
			}

			//custom query to use instead of base query
			if(isset($_GET['customQuery']))
			{
				$customQuery = urlencode($_GET['customQuery']);

				$report .= 'Custom Query: '.$customQuery.PHP_EOL;

				$baseQuery = 'query='.$customQuery;
			}
			else
			{
				//base query - search for ETDs with an embargo date one week in the future
				$baseQuery = 'query=(dc.type:Thesis%20OR%20dc.type:Dissertation)%20AND%20dc.rights.embargodate:"'.ONE_WEEK_LATER.'"';

				//with posters as well
				//$baseQuery = 'query=(dc.type:Thesis%20OR%20dc.type:Dissertation%20OR%20dc.type:Poster)%20AND%20dc.rights.embargodate:'.ONE_WEEK_LATER;
			}

			//array to hold list of handles of items that match the query
			$embargoExpiringHandles = [];
					
			$page = 0;

			//continue paging until no further results are returned
			$continuePaging = TRUE;

			while($continuePaging)
			{
				if(!empty($page))
				{
					$query = $baseQuery.'&page='.$page;
				}
				else
				{
					$query = $baseQuery;
				}

				echo $query.PHP_EOL;
				
				$response = dspaceSearch($query);

				if($response['status'] == 'success')
				{
					$results = json_decode($response['body'], TRUE);

					$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

					echo $totalPages.PHP_EOL;
					
					foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
					{
						$item = $result['_embedded']['indexableObject'];

						$embargoExpiringHandles[] = $result['_embedded']['indexableObject']['handle'];
					}

					//stop paging if no more results
					if(!isset($results['_embedded']['searchResult']['_links']['next']))
					{
						$continuePaging = FALSE;
					}
					else
					{
						if($page >= $totalPages)
						{
							$continuePaging = FALSE;
						}
					}
				}
			}
		}
	
		//send embargo expiration notices
		if($notificationsToSend['EtdEmbargoExpirationReminder'])
		{
			if(!isset($embargoExpiringHandles))
			{
				$embargoExpiringHandles = $requestedHandles;
			}
			
			foreach($embargoExpiringHandles as $itemHandle)
			{
				$recordTypeCounts['all']++;

				$report .= $itemHandle.PHP_EOL;

				$idInIRTS = 'repository_'.$itemHandle;

				$response = dspaceGetItemByHandle($itemHandle);

				if($response['status'] == 'success')
				{
					$item = json_decode($response['body'], TRUE);
				
					$itemID = $item['id'];
	
					$itemMetadata = $item['metadata'];
						
					$record = dSpaceMetadataToArray($itemMetadata);

					$recipients = identifyEtdNotificationRecipients($record);

					unset($recipients['gpcEmail']);

					$studentName = $recipients['studentName'];

					unset($recipients['studentName']);

					$message = '<p>The embargo on public access to the full text of the '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0].'" by '.$studentName.', available at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle. '</a> will expire on '.$record['dc.rights.embargodate'][0].'.</p>
					<p>If you are not ready to release this '. strtolower($record['dc.type'][0]).' to public access and an embargo extension is required, please reply to this email with a reason and requested period of embargo extension.</p>
						<p>Regards,</p>
						<p>The KAUST Repository Team, University Library</p>';

					if(isset($recipients['errors']))
					{
						$report .= ' - error found : '.print_r($recipients, TRUE).PHP_EOL;

						$to = IR_EMAIL;

						$subject = "ETD embargo expiration notice failed to send";

						$message = '<p>The ETD notification message was not sent due to the below errors: '.print_r($recipients, TRUE).'</p><p>Please add the missing emails and send the below message manually:</p>'.$message;

						if(mail($to,$subject,$message,$headers))
						{
							$report .= ' - error notice sent to '.$to.PHP_EOL;

							$recordTypeCounts['error sent to repository']++;
						}
						else
						{
							$recordTypeCounts['failed to send']++;
						}
					}
					else
					{
						$to = implode(", ", $recipients);

						//$to = IR_EMAIL;

						$subject = $record['dc.type'][0]." embargo expiration reminder";

						if(mail($to,$subject,$message,$headers))
						{
							$report .= ' - Embargo expiration notification sent to : '.$to.PHP_EOL;

							$recordTypeCounts['ETD embargo expiration reminder sent to author and advisor']++;
						}
						else
						{
							$recordTypeCounts['failed to send']++;
						}
					}

					$sent = saveValue('irts', $idInIRTS, 'irts.date.etdEmbargoExpirationNotificationSent', 1, date('Y-m-d'), NULL);

					ob_flush();
					set_time_limit(0);
					sleep(60);
				}
				else
				{
					print_r($response);
				}
			}
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);
		//echo $summary ;
		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
