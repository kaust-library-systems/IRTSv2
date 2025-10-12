<?php
	function sendETDRelatedNotices($report, $errors, $recordTypeCounts)
	{
		global $irts;
		//Get initial CSRF token and set in session
		
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();
		
		//possible notification types
		$notificationTypes = array(
			'archivalNoticeToAuthorAdvisorGPC',
			'archivalNoticeToRegistrar',
			'ReferenceListRequestToAuthor'
		);
		
		//array setting whether or not to send each type
		$notificationsToSend = [];

		$recordTypeCounts['archival notice sent to author, advisor and GPC'] = 0;
		$recordTypeCounts['archival notice sent to registrar'] = 0;
		$recordTypeCounts['embargo expiration reminder sent to author and advisor'] = 0;
		$recordTypeCounts['Reference List Request sent to the Author'] = 0;
		$recordTypeCounts['error sent to repository'] = 0;

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
			
			
			//check for newly archived ETDs for which notifications have not been sent			
			$newlyArchivedHandles = getValues($irts, "SELECT idInSource FROM `metadata`
					WHERE `source`LIKE 'repository'
					AND `field` LIKE 'dc.date.accessioned'
					AND `value` LIKE '".TODAY."%'
					AND deleted IS NULL
					AND idInSource IN (
						SELECT idInSource from `metadata`
						WHERE `source` LIKE 'repository'
						AND `field` LIKE 'dc.type'
						AND `value`in('Dissertation','Thesis')
						AND deleted IS NULL
						)
					AND CONCAT('repository_',idInSource) NOT IN (
						SELECT idInSource FROM metadata
						WHERE source = 'irts'
						AND field = 'irts.date.etdArchivingNotificationSent'
						AND value = '".TODAY."'
						AND deleted IS NULL)
					AND CONCAT('repository_',idInSource) IN (
						SELECT idInSource FROM metadata
						WHERE source = 'irts'
						AND field = 'irts.checked.status'
						AND value IN ('complete')
						AND deleted IS NULL
						AND parentRowID IN (
							SELECT rowID FROM metadata
							WHERE source = 'irts'
							AND field = 'irts.checked.process'
							AND value = 'manageETDs'
							AND deleted IS NULL
						)
					)", array('idInSource'));
			
		}

		//send archiving notices		
		if($notificationsToSend['archivalNoticeToAuthorAdvisorGPC'] || $notificationsToSend['archivalNoticeToRegistrar']|| $notificationsToSend['ReferenceListRequestToAuthor'])
		{
			//send reference list requests for ETDs archived over last year 
			if(!isset($newlyArchivedHandles))
			{
				$newlyArchivedHandles = $requestedHandles;
			}
			
			foreach($newlyArchivedHandles as $itemHandle)
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
					
					
					//send to author, advisor and GPC
					if($notificationsToSend['archivalNoticeToAuthorAdvisorGPC'])
					{
						$recipients = identifyEtdNotificationRecipients($record);

						$studentName = $recipients['studentName'];

						unset($recipients['studentName']);

						//message
						if(isset($record['dc.rights.embargodate']))
						{
							$message = '<p>The '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0] .'" by '.$studentName. ' has been archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle.'</a>.</p>
									<p>The full text of this '.strtolower($record['dc.type'][0]).' is under restricted access and will become available to the public after the expiration of the embargo on '.$record['dc.rights.embargodate'][0].'. An extension can be requested at that time if a further embargo is required.</p>
									<p>Thank you for the deposit and for using our services. Please write to us if you have any questions.</p>
									<p>Regards,</p>
									<p>The KAUST Repository Team, University Library</p>';
						}
						else
						{
							$message = '<p>The '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0] .'" by '.$studentName. ' has been archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle.'</a>. The full text is available for immediate public access and you can add this permanent URL to your academic profiles and share via the internet. Thank you for the deposit and for using our services. Please write to us if you have any questions.</p>
									<p>Regards,</p>
									<p>The KAUST Repository Team, University Library</p>';
						}

						if(isset($recipients['errors']))
						{
							$report .= ' - error found : '.print_r($recipients, TRUE).PHP_EOL;

							$to = IR_EMAIL;

							$subject = "ETD archiving notice failed to send";

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
							

							$subject = $record['dc.type'][0]." archived in the KAUST repository";

							if(mail($to,$subject,$message,$headers))
							{
								$report .= ' - archival notice sent to : '.$to.PHP_EOL;

								$recordTypeCounts['archival notice sent to author, advisor and GPC']++;
							}
							else
							{
								$recordTypeCounts['failed to send']++;
							}
						}
					}
				

					//send to registrar
					if($notificationsToSend['archivalNoticeToRegistrar'])
					{
						
						$recipients = identifyEtdNotificationRecipients($record);

						$studentName = $recipients['studentName'];
						
						
						$registrarMessage = '<p>The '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0] .'" by '.$studentName. ' with KAUST ID '.$record['dc.person.id'][0].' was archived in the KAUST Repository at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle.'</a> on '. explode("T",$record['dc.date.accessioned'][0])[0].'.</p>
												<p>Regards,</p>
												<p>The KAUST Repository Team, University Library</p>';

						$subject = "New ".strtolower($record['dc.type'][0])." archived in the KAUST repository";

						if(mail(REGISTRAR_EMAILS,$subject,$registrarMessage,$headers))
						
						{
							$sent = saveValue('irts', $idInIRTS, 'irts.date.etdArchivingNotificationSent', 1, date('Y-m-d'), NULL);

							$report .= ' - archival notice sent to : '.REGISTRAR_EMAILS.PHP_EOL;
							$recordTypeCounts['archival notice sent to registrar']++;
						}
						else
						{
							$recordTypeCounts['failed to send']++;
						}
					}
					
					// send reference list request to author
					if($notificationsToSend['ReferenceListRequestToAuthor'])
					{
						$recipients = identifyEtdNotificationRecipients($record);

						$studentName = $recipients['studentName'];
						$studentEmail = $recipients['studentEmail'];
						
						
						$ReferenceListMessage = '<p>Dear '.$studentName.',</p>
						<p>The University Library is experimenting with methods to create machine-readable links between theses and dissertations and the publications that they cite. We have looked at a number of automated processes to accomplish this based on the reference lists in thesis PDFs. However, the results are often incomplete or inaccurate.</p>
						
						<p>As a result, we are piloting an option for students to provide their own reference list as a Bibtex export from their citation management software (Zotero, EndNote, etc.). If you are interested in adding a machine readable reference list to the record for your '. strtolower($record['dc.type'][0]).' "'.$record['dc.title'][0] .'" in the repository at <a href="http://hdl.handle.net/'.$itemHandle.'">http://hdl.handle.net/'.$itemHandle.'</a>, we invite you to attach the appropriate reference list in reply to this email.</p>
						<p>By providing the reference list you also confirm to us that it is an accurate and complete list of the references included in the final version of your '. strtolower($record['dc.type'][0]).'.
						This is entirely voluntary, and not a requirement related to your degree. This request does not affect any embargo placed on your work.</p>
						
						<p>If you have any questions or concerns, please let us know.</p>
												<p>Regards,</p>
												<p>The KAUST Repository Team, University Library</p>';

						$subject = "Adding ".strtolower($record['dc.type'][0])." reference list in the KAUST repository";
						
						

						if(mail($studentEmail,$subject,$ReferenceListMessage,$headers))
							
						
						{
							$sent = saveValue('irts', $idInIRTS, 'irts.date.etdArchivingNotificationSent', 1, date('Y-m-d'), NULL);

							$report .= ' - Reference list request sent to : '.$studentEmail.PHP_EOL;
							$recordTypeCounts['Reference List Request sent to the Author']++;
						}
						else
						{
							$recordTypeCounts['failed to send']++;
						}
					}
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


		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);
		//echo $summary ;
		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
