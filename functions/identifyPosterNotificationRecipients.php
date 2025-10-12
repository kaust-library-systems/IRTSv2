<?php
	//Define function to identify the persons to receive notifications about an ETD
	function identifyPosterNotificationRecipients($record)
	{		
		$errors = array();
		$recipients = ['AuthorName' => '', 'AuthorEmail' => '', 'PIEmail' => ''];
		
		if(isset($record['dc.contributor.author']))
		{
			$AuthorLastName = explode(',', $record['dc.contributor.author'][0])[0];
			$AuthorFirstName = explode(',', $record['dc.contributor.author'][0])[1];
			$AuthorName = $AuthorFirstName. " " .$AuthorLastName;
			$recipients['AuthorName'] = $AuthorName;
			 //echo $AuthorName;
		}

		//Author and PI email identified based on submitter information
		if(isset($record['dc.description.provenance']))
		{
			foreach($record['dc.description.provenance'] as $place => $value)
			{
				if(strpos($value, 'Email :') !== FALSE)
				{
					$AuthorEmail = explode('Email :', $value)[1];
					$recipients['AuthorEmail'] = trim($AuthorEmail);
					break;
				}
				elseif(strpos($value, 'Email:') !== FALSE)
				{
					$AuthorEmail = explode('Email:', $value)[1];
					$recipients['AuthorEmail'] = trim($AuthorEmail);
					break;
				}
			}
			foreach($record['dc.description.provenance'] as $place => $value)
			{
				if(strpos($value, 'PI Email :') !== FALSE)
				{
					$PIEmail = explode('PI Email :', $value)[1];
						
					$recipients['PIEmail'] = trim($PIEmail);
				}
				elseif(strpos($value, 'PI Email:') !== FALSE)
				{
					$PIEmail = explode('PI Email:', $value)[1];
						
					$recipients['PIEmail'] = trim($PIEmail);
				}
			}
		}
			
		print_r($recipients);		
			
		if(empty($recipients['AuthorEmail']))
		{
			$errors[] = 'No student email identified';
		}
		if(count($errors) !== 0)
		{
			$recipients['errors'] = $errors;
		}

		return $recipients;                                      
	}