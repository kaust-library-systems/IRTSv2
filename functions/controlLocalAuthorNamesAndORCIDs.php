<?php
	//Define function to correct name variants and add ORCIDs based on control name and name variants
	function controlLocalAuthorNamesAndORCIDs($handle, $authors, $localAuthors)
	{
		global $irts;
		
		$matchedPersons = array();
		$matchfound = '';
		$unmatchedLocalAuthors = array();
		
		$originalAuthors = $authors;
		$controlledAuthors = $originalAuthors;
		
		$originalLocalAuthors = $localAuthors;
		$controlledLocalAuthors = $originalLocalAuthors;
		
		if(!empty($originalLocalAuthors));
		{
			foreach($localAuthors as $localAuthor)
			{					
				if(!empty($localAuthor[LOCAL_AUTHOR_FIELD]))
				{
					$match = checkLocalPerson($localAuthor);

					if(!empty($match['local.person.id']))
					{
						$matchedPersons[] = $match;

						if($match['dc.identifier.orcid']!==$localAuthor['dc.identifier.orcid'])
						{
							if($existingOrcid==='no orcid available')
							{										
								$nameWithOrcid = $controlName . '::' . $orcid;									
							}
							elseif($existingOrcid===$orcid)
							{
								$nameWithOrcid = $controlName . '::' . $orcid;
							}
							elseif($existingOrcid!==$orcid)
							{
								$nameWithOrcid = $controlName . '::' . $existingOrcid;
								
								$message .= '<br>Special Case! - Existing ORCID without control ORCID match: Local Author: '.$localAuthor.' - Control Name: '. $controlName . '::' . $orcid;

								$message .= '<br>Handle: '.$handle.'<br><hr>';
							}

							//Check if change is needed
							if($localAuthor!==$nameWithOrcid)
							{
								$message .= '<br>Local Author: ' . $localAuthor . ' - Name with ORCID: ' . $nameWithOrcid;
								$controlledLocalAuthors = str_replace($localAuthor, $nameWithOrcid, $controlledLocalAuthors);
								$matchfound = 'yes';
							}
						}
						elseif($existingOrcid!=='no orcid available')
						{
							$nameWithOrcid = $controlName . '::' . $existingOrcid;							
							
							//Check if change is needed
							if($localAuthor!==$nameWithOrcid)
							{
								$message .= '<br>Special Case! - Existing ORCID, but no known control ORCID!!!';
								$message .= '<br>Local Author: ' . $localAuthor . ' - Name with ORCID: ' . $nameWithOrcid;
								$controlledLocalAuthors = str_replace($localAuthor, $nameWithOrcid, $controlledLocalAuthors);
								$matchfound = 'yes';
							}								
						}							
						elseif($localAuthor!==$controlName)
						{
							$message .= '<br>Local Author: ' . $localAuthor . ' - Control Name: ' . $controlName;
							$controlledLocalAuthors = str_replace($localAuthor, $controlName, $controlledLocalAuthors);
							$matchfound = 'yes';
						}								
						unset($match);
					}
				}
			}
			
			if(!empty($originalAuthors))
			{
				$authorsToCheck = explode('||', $originalAuthors);
				
				foreach($authorsToCheck as $author)
				{					
					if(!empty($author))
					{						
						$authorName = $author;
						$email = 'no email available';						
						$existingOrcid = 'no orcid available';
						$scopusid = 'no scopusid available';
						
						explodeAuthor($authorName, $email, $existingOrcid, $scopusid);
						
						$match = checkPerson(array("orcid" => $existingOrcid, "name" => $authorName, "scopusid" => $scopusid, "email" => $email));
			
						if(!empty($match['localID']))
						{
							$controlName = $match['controlName'];
							if(strpos($controlledLocalAuthors, $controlName)!==FALSE)
							{
								if(!empty($match['orcid']))
								{
									$orcid = $match['orcid'];
									if($existingOrcid==='no orcid available')
									{										
										$nameWithOrcid = $controlName . '::' . $orcid;
									}
									elseif($existingOrcid===$orcid)
									{
										$nameWithOrcid = $controlName . '::' . $orcid;	
									}
									elseif($existingOrcid!==$orcid)
									{
										$nameWithOrcid = $controlName . '::' . $existingOrcid;
										
										$message .= '<br>Special Case! - Existing ORCID without control ORCID match: Author: '.$author.' - Control Name: '. $controlName . '::' . $orcid;

										$message .= '<br>Handle: '.$handle.'<br><hr>';
									}
									
									//Check if change is needed
									if($author!==$nameWithOrcid)
									{
										$message .= '<br>Author: ' . $author . ' - Name with ORCID: ' . $nameWithOrcid;
										$controlledAuthors = str_replace($author, $nameWithOrcid, $controlledAuthors);
										$matchfound = 'yes';
									}														
								}
								elseif($existingOrcid!=='no orcid available')
								{
									$nameWithOrcid = $controlName . '::' . $existingOrcid;
									
									//Check if change is needed
									if($author!==$nameWithOrcid)
									{
										$message .= '<br>Special Case! - Existing ORCID, but no known control ORCID!!!';
										$message .= '<br>Author: ' . $author . ' - Name with ORCID: ' . $nameWithOrcid;
										$controlledAuthors = str_replace($author, $nameWithOrcid, $controlledAuthors);
										$matchfound = 'yes';
									}								
								}	
								elseif($author!==$controlName)
								{
									$message .= '<br>Author: ' . $author . ' - Control Name: ' . $controlName;
									$controlledAuthors = str_replace($author, $controlName, $controlledAuthors);
									$matchfound = 'yes';
								}
							}
							unset($match);
						}							
					}
				}
			}
		}	
		$authors = $controlledAuthors;		
		$localAuthors = $controlledLocalAuthors;
		
		//return $matchfound;
		//return $unmatchedLocalAuthors;
		
		return $matchedPersons;
	}	
