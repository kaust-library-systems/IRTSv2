<?php
	function sendPosterEmbargoExpirationNotices($report, $errors, $recordTypeCounts)
	{
		global $irts;

		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();
		
		$errors = array();

		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		//CC repository email
		$headers .= "From: " .IR_EMAIL. "\r\n";
		$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";
		
		$recordTypeCounts['Poster embargo expiration reminder sent to author and PI'] = 0;
		$recordTypeCounts['error sent to repository'] = 0;

		if(isset($_GET['itemHandle']))
		{
			$report .= 'Item Handle Requested:'.$_GET['itemHandle'].PHP_EOL;
			
			// if request is set to run for an individual handle, then the notificationToSend must also be set
			$report .= 'Notification Requested:'.$_GET['notificationToSend'].PHP_EOL;
					
			$requestedHandles = array($_GET['itemHandle']);
		}
		else
		{ 
	        //custom query to use instead of base query
			if(isset($_GET['customQuery']))
			{
				$customQuery = urlencode($_GET['customQuery']);

				$report .= 'Custom Query: '.$customQuery.PHP_EOL;

				$baseQuery = 'query='.$customQuery;
			}
			else 
			{
				$baseQuery ='query=(dc.type:Poster)%20AND%20dc.rights.embargodate:"'.ONE_WEEK_LATER.'"';
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
						$page++;
						if($page >= $totalPages)
						{
							$continuePaging = FALSE;
						}
					}
				}
			}
		}
				
		//send embargo expiration notices
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
				echo $itemID.PHP_EOL;
	
				$itemMetadata = $item['metadata'];
						
				$record = dSpaceMetadataToArray($itemMetadata);
				//print_r($record);
				$recipients = identifyPosterNotificationRecipients($record);
				$AuthorName = $recipients['AuthorName'];

				unset($recipients['AuthorName']);

				if(isset($record['dc.rights.embargodate']))
				{
					
					$message = '<p>The embargo on public access to the full text of the '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0].'" by '.$AuthorName.', available at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle. '</a> will expire on '.$record['dc.rights.embargodate'][0].'.</p>
					<p>If you are not ready to release this '. strtolower($record['dc.type'][0]).' to public access and an embargo extension is required, please reply to this email with a reason and requested period of embargo extension.</p>
					<p>Regards,</p>
					<p>The KAUST Repository Team, University Library</p>';					
					if(isset($recipients['errors']))
					{
						$report .= ' - error found : '.print_r($recipients, TRUE).PHP_EOL;

						$to = IR_EMAIL;
						
						$subject = "poster embargo expiration notice failed to send";

						$message = '<p>The EPoster notification message was not sent due to the below errors: '.print_r($recipients, TRUE).'</p><p>Please add the missing emails and send the below message manually:</p>'.$message;

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
							$report .= ' PostereEmbargo expiration notification sent to : '.$to.PHP_EOL;
							$recordTypeCounts['Poster embargo expiration reminder sent to author and PI']++;
							print_r($recipients);		
							print('Poster embargo expiration reminder sent to: '.$recipients['AuthorEmail'].'&'.$recipients['PIEmail'].PHP_EOL);
						}
						else
						{
							$recordTypeCounts['failed to send']++;
						} 
					}
				
			    }
 
				$sent = saveValue('irts', $idInIRTS, 'irts.date.posterEmbargoExpirationNotificationSent', 1, date('Y-m-d'), NULL);

				ob_flush();
				set_time_limit(0);
				sleep(60);
			}
			else
			{
				print_r($response);
			}
		}
		

		$summary = saveReport($irts,__FUNCTION__, $report, $recordTypeCounts, $errors);
		//echo $summary ;
		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
