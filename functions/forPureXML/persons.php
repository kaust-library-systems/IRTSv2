<?php
	//Define function to add person information to Pure XML
	function persons($authors, $kaustAuthors, $authorsWithFullAffs, $authorsWithControlledScopusAffs, $authorsWithScopusIDs, $affsWithScopusIds, &$allAuthorsDeptIds, &$ownerid, $pubdate)
	{
		global $message, $control, $pubs, $kaustDeptsRows, $scopusOrgRows, $purexml;	
		
		$purexml .= '<!--Optional:-->
		<v1:persons>
			<!--1 or more repetitions:-->';
			
		//Count of author iteration to match author to authorWithAffs
		$authorCount = 0;	
			
		foreach($authors as $author)
		{				
			$name = '';
			$orcid = 'no known id';
			$scopusid = 'no known id';
			$orgunitnumbers = array();
			
			if(strpos($author, '::') !== FALSE)
			{
				$authorparts = explode('::', $author);
				$name = $authorparts[0];
				$orcid = $authorparts[1];
			}
			else
			{
				$name = $author;
			}
			
			$nameParts = explodeName($name);
			
			if(!empty($authorsWithFullAffs)&&count($authors)===count($authorsWithFullAffs))
			{
				$fullAffs = $authorsWithFullAffs[$authorCount]['affiliation'];												
			}
			else
			{
				$fullAffs = '';
			}
			
			if(!empty($authorsWithControlledScopusAffs)&&count($authors)===count($authorsWithControlledScopusAffs))
			{
				$authorWithControlledScopusAff = $authorsWithControlledScopusAffs[$authorCount];
				if(strpos($authorWithControlledScopusAff, ':affs:')!==FALSE)
				{
					$authorWithControlledScopusAffParts = explode(':affs:', $authorWithControlledScopusAff);
					$controlledScopusAffs = $authorWithControlledScopusAffParts[1];
					$controlledScopusAffs = array_unique(array_filter(explode(':aff:', $controlledScopusAffs)));
				}
				else
				{
					$controlledScopusAffs = array();
				}
			}
			else
			{
				$authorWithControlledScopusAff = '';
				$controlledScopusAffs = array();
			}

			if(!empty($authorsWithScopusIDs)&&count($authors)===count($authorsWithScopusIDs))
			{
				$authorWithScopusID = $authorsWithScopusIDs[$authorCount];
				if(strpos($authorWithScopusID, '::')!==FALSE)
				{
					$scopusparts = explode('::', $authorWithScopusID);
					$scopusname = $scopusparts[0];
					$scopusid = $scopusparts[1];
				}
			}
			else
			{
				$authorWithScopusID = '';
				$scopusname = '';
			}			

			//Check for KAUST Aff
			$kaustauthor = '';
			$infullaff = '';
			$incontrolledaff = '';
			
			//If there are full affs, do the KAUST aff check, only check the controlled Scopus affs if there are no full affs
			//This was prompted by a single item (DOI: 10.1145/2661229.2661260) where the Scopus affs are wrongly ordered
			if(!empty($fullAffs))
			{
				//Check if in full aff
				if(institutionNameInString($fullAffs))				
				{
					$kaustauthor = 'yes';
					$infullaff = 'yes';
				}
				
				//Check if in controlled Scopus aff, but do not mark as a KAUST author on match
				foreach($controlledScopusAffs as $aff)
				{
					if(institutionNameInString($aff))
					{
						$incontrolledaff = 'yes';
					}								
				}
			}	
			else
			{				
				//Check if in controlled Scopus aff
				foreach($controlledScopusAffs as $aff)
				{
					if(institutionNameInString($aff))
					{
						$kaustauthor = 'yes';
						$incontrolledaff = 'yes';
					}								
				}
			}	

			/* if($infullaff == 'yes' && $incontrolledaff == '')
			{
				$message .= '<br> - KAUST affiliation not controlled by Scopus: '. $fullAffs;
			} */							
			
			$purexml .= '<v1:author>
			<v1:role>author</v1:role>
			<!--Optional:-->';
			if($kaustauthor === 'yes' || in_array($author, $kaustAuthors))
			{
				$thisAuthorDeptIds = array();
				
				$person = checkPerson(array("kaustid" => "no known id", "orcid" => $orcid, "name" => $name, "scopusid" => $scopusid));
				
				addInternalPerson($person);				
				
				if(!empty($person['kaustID']))
				{
					$thisAuthorDeptIds = checkDeptIDs($person, $pubdate);
					$allAuthorsDeptIds = array_merge($allAuthorsDeptIds, $thisAuthorDeptIds);					
				}		
				
				if(!empty($infullaff))
				{
					checkInAffForDepts($fullAffs, $kaustDeptsRows, $orgunitnumbers, $ownerid);
				}	
				
				//Process full affs first for internal authors
				if(!empty($fullAffs))
				{
					$purexml .= '<!--Optional:-->
					<v1:organisations>
					<!--1 or more repetitions:-->';
													
					$affCount = 0;
					$fullAffs = array_filter(explode(':aff:', $fullAffs));
					foreach($fullAffs as $aff)
					{									
						if(institutionNameInString($aff))
						{
							addKaustDeptAffs($orgunitnumbers, $thisAuthorDeptIds, $aff);
							$affCount++;
						}
						else
						{
							if(!empty($controlledScopusAffs)&&count($controlledScopusAffs)>$affCount)
							{							
								if(isset($controlledScopusAffs[$affCount]))
								{
									$aff = $controlledScopusAffs[$affCount];
									if(institutionNameInString($aff))
									{
										addKaustDeptAffs($orgunitnumbers, $thisAuthorDeptIds, $aff);
									}
									else
									{	
										addExternalAff($aff, $affsWithScopusIds);
									}
								}		
							}
							else
							{
								addExternalAff($aff, '');
							}
							$affCount++;
						}											
					}
					$purexml .= '</v1:organisations>';	
				}
				elseif(!empty($controlledScopusAffs))
				{
					$purexml .= '<!--Optional:-->
					<v1:organisations>';
							
					foreach($controlledScopusAffs as $aff)
					{									
						//Check for KAUST Aff
						if(institutionNameInString($aff))
						{
							addKaustDeptAffs($orgunitnumbers, $thisAuthorDeptIds, $aff);							
						}
						else
						{
							addExternalAff($aff, $affsWithScopusIds);
						}
					}
					$purexml .= '</v1:organisations>';
				}
			}													
			else
			{
				addExternalPerson($nameParts);						
				
				//Process Full Affs first for external authors, this is needed because KAUST affiliation check is based on full aff check (sample problem record: DOI: 10.1128/genomeA.00014-12)
				if(!empty($fullAffs))
				{
					//$message .= '<br>- Processing full affs...';
					
					$purexml .= '<!--Optional:-->
					<v1:organisations>';
					
					$affCount = 0;
					$fullAffs = array_filter(explode(':aff:', $fullAffs));
					
					foreach($fullAffs as $aff)
					{
						if(!empty($controlledScopusAffs)&&count($controlledScopusAffs)>$affCount)
						{							
							if(isset($controlledScopusAffs[$affCount]))
							{
								if(!institutionNameInString($controlledScopusAffs[$affCount]))
								{
									$aff = $controlledScopusAffs[$affCount];
									addExternalAff($aff, $affsWithScopusIds);							
								}
								else
								{
									$message .= '<br>- KAUST related string in '.$controlledScopusAffs[$affCount].' so we will use the full aff: '.$aff;
									addExternalAff($aff, '');
								}
							}		
						}
						else
						{
							$message .= '<br>- controlled scopus aff does not exist or there is an aff count mismatch, we will try to match '.$aff.' on an existing Scival Org Name';
							addExternalAff($aff, '');
						}
						$affCount++;
					}					
					$purexml .= '</v1:organisations>';
				}
				elseif(!empty($controlledScopusAffs))
				{
					$message .= '<br>- No full affs, processing Scopus control affs...';
					if($incontrolledaff!=='yes')
					{
						$purexml .= '<!--Optional:-->
						<v1:organisations>';
								
						foreach($controlledScopusAffs as $aff)
						{									
							addExternalAff($aff, $affsWithScopusIds);													
						}
						$purexml .= '</v1:organisations>';
					}
					else
					{
						$message .= '<br> - KAUST author incorrectly marked as external author?!?!';
					}
				}				
			}
			$purexml .= '</v1:author>';
			$authorCount++;					
		}
		$purexml .= '</v1:persons>';
	}		
