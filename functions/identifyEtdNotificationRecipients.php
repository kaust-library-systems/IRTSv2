<?php
	//Define function to identify the persons to receive notifications about an ETD
	function identifyEtdNotificationRecipients($record)
	{
		$errors = array();
		$recipients = ['gpcEmail' => '', 'studentName' => '', 'studentEmail' => '', 'advisorEmail' => ''];

		$new = TRUE;
		
		//For ETDs archived before the introduction of the new workflow, recipients need to be identified differently as the students are now the submitters. However, for the embargo reminder notifications, we still need to have the old method of identifying recipients available for use with older items.
		if($record['dc.date.accessioned'][0] < '2022-04-17')
		{
			$new = FALSE;
		}

		if($new) //archived with student as submitter
		{
			//GPC email identified from metadata
			if(isset($record['kaust.gpc']))
			{
				$recipients['gpcEmail'] = $record['kaust.gpc'][0];
			}
			else
			{
				$errors[] = 'No GPC email identified';
			}

			//Student name and email identified based on submitter information
			if(isset($record['dc.description.provenance']))
			{
				foreach($record['dc.description.provenance'] as $place => $value)
				{
					if(strpos($value, "Submitted by") !== false)
					{
						preg_match('#\((.*?)\)#', $value, $match);

						$recipients['studentEmail'] =  $match[1];
						
						$value = str_replace('Submitted by ', '', $value);
						
						$recipients['studentName'] = explode(' (', $value)[0];
					}
				}
			}
			
			if(empty($recipients['studentEmail']))
			{
				$recipients['studentEmail'] = '';
				
				$errors[] = 'No student email identified';
			}
		}
		else //archived with GPC as submitter (only needed for embargo expiration notifications, GPC email no longer needed)
		{
			//student email by KAUST ID and name
			if(isset($record['dc.person.id']))
			{
				$studentID = $record['dc.person.id'][0];
				$studentName = $record['dc.contributor.author'][0];
				$recipients['studentName'] = explodeName($studentName)['fullName'];
				
				$match = checkPerson(array('localID'=>$studentID, 'name'=>$studentName));
				
				if(!empty($match['email']))
				{
					$recipients['studentEmail'] = $match['email'];
				}
				else
				{
					$recipients['studentEmail'] = '';
					
					$errors[] = 'No student email identified';
				}
			}
		}
		
		//advisor email identified based on name and/or ORCID (same for both new and old items)
		if(isset($record['orcid.advisor']))
		{
			$advisorParts = explode('::', $record['orcid.advisor'][0]);
						
			$advisorName = $advisorParts[0];
			
			if(isset($advisorParts[1]))
			{
				$advisorORCID = $advisorParts[1];
				$match = checkPerson(array('orcid'=>$advisorORCID, 'name'=>$advisorName));
			}
			else
			{
				$match = checkPerson(array('name'=>$advisorName));
			}
			
			if(!empty($match['localID']))
			{
				$recipients['advisorEmail'] = $match['email'];
			}
			else
			{
				$recipients['advisorEmail'] = '';
				
				$errors[] = 'No advisor email identified';
			}
		}
		
		if(count($errors) !== 0)
		{
			$recipients['errors'] = $errors;
		}

		return $recipients;
	}
