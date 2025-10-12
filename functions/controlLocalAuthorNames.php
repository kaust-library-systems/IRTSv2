<?php
	//Define function to correct name variants and add ORCIDs based on control name and name variants
	function controlLocalAuthorNames(&$authors, &$localAuthors)
	{
		//global $controlledAuthors, $originalAuthors, $controlledLocalAuthors, $originalLocalAuthors, $matchfound, $unmatchedLocalAuthors, $message, $control;
		
		global $handle, $matchfound, $unmatchedLocalAuthors, $message, $control;
		
		$matchedPersons = array();
		
		$originalAuthors = $authors;
		$controlledAuthors = $originalAuthors;
		
		$originalLocalAuthors = $localAuthors;
		$controlledLocalAuthors = $originalLocalAuthors;		
		
		if(!empty($originalLocalAuthors));
		{
			$localAuthorsArray = explode('||', $originalLocalAuthors);
			
			foreach($localAuthorsArray as $localAuthor)
			{					
				if(!empty($localAuthor))
				{
					$localAuthorName = $localAuthor;
					$email = 'no email available';						
					$existingOrcid = 'no orcid available';
					$scopusid = 'no scopusid available';
					
					explodeAuthor($localAuthorName, $email, $existingOrcid, $scopusid);
					
					$match = checkPerson(array("orcid" => $existingOrcid, "name" => $localAuthorName, "scopusid" => $scopusid, "email" => $email));
			
					if(!empty($match['localID']))
					{
						$matchedPersons[] = $match;
						
						$controlName = $match['controlName'];								
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
					else
					{
						//array_push($unmatchedLocalAuthors, array($handle, $localAuthor));						
						if(strpos($localAuthor, '::scopusID') !== FALSE)
						{
							$message .= '<br>Local author with Scopus ID: ' . $localAuthor . ' - Local author name only: ' . $localAuthorName;
							$controlledLocalAuthors = str_replace($localAuthor, $localAuthorName, $controlledLocalAuthors);
							$matchfound = 'yes';
						}
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
							elseif(strpos($author, '::scopusID') !== FALSE)
							{
								$controlledAuthors = str_replace($author, $authorName, $controlledAuthors);
								$matchfound = 'yes';
							}
							unset($match);
						}							
						elseif(strpos($author, '::scopusID') !== FALSE)
						{
							$controlledAuthors = str_replace($author, $authorName, $controlledAuthors);
							$matchfound = 'yes';
						}
					}
				}
			}
		}	
		$authors = $controlledAuthors;		
		$localAuthors = $controlledLocalAuthors;
		
		return $matchedPersons;
	}	
