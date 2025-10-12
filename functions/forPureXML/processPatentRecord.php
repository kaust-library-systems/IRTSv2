<?php	
	//Define function to process a patent record
	function processPatentRecord()
	{
		global $languageCode, $i, $message, $control, $pubs, $repo, $purexml, $handle, $kaustDeptsRows, $today;
		
		$result = $repo->query("
		SELECT `collection`, `dc.type[en]`, `dc.title[en]`, `dc.contributor.author[en]`, `dc.contributor.assignee[en]`, `kaust.author[en]`, `dc.description.abstract[en]`, r.`dc.identifier.uri[en]`, r.`dc.identifier.patentnumber[en]`, r.`dc.identifier.applicationnumber[en]`, r.`originalFileURLs`, `dc.publisher[en]`, `dc.subject[en]`, `dc.relation.url[en]`, `dc.rights[en]`, r.`dc.eprint.version[en]`, `dc.date.issued[en]`
		FROM repository r
		WHERE r.`dc.identifier.uri[en]` = '$handle'");
				
		$row_cnt = $result->num_rows;
		if($row_cnt===1)
		{				
			$row = $result->fetch_assoc();
			
			$i++;
			$message .= '<hr><br>Handle: '.$handle;
			
			$authors = explode('||', $row['dc.contributor.author[en]']);
		
			$collection = $row['collection'];
			$type = $row['dc.type[en]'];
			$title = $row['dc.title[en]'];
					
			$kaustauthors = $row['kaust.author[en]'];
			$abstract = $row['dc.description.abstract[en]'];		
			$pubdate = $row['dc.date.issued[en]'];
			
			$patentnumbers = explode('||', $row['dc.identifier.patentnumber[en]']);
			$applicationnumbers = explode('||',$row['dc.identifier.applicationnumber[en]']);
			
			$handle = $row['dc.identifier.uri[en]'];
					
			$urls = explode('||', htmlspecialchars($row['dc.relation.url[en]'], ENT_QUOTES));
			$files = explode('||', htmlspecialchars($row['originalFileURLs'], ENT_QUOTES));
			
			$kaustGrantNumbers = '';
			$acknowledgement = '';				
									
			$allAuthorsDeptIds = array();
			$ownerid = '';
										
			if($type=='Patent')	
			{				
				$purexml .= '<v1:patent id="'.$handle.'" subType="patent">';
				
				$purexml .= '<v1:peerReviewed>false</v1:peerReviewed>';
				
				commonElements($pubdate, $title, $abstract);
				
				$purexml .= '<!--Optional:-->
				<v1:persons>
					<!--1 or more repetitions:-->';
					
				foreach($authors as $author)
				{				
					$name = '';
					$orcid = 'no known id';
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
								
					$purexml .= '<v1:author>
					<v1:role>inventor</v1:role>
					<!--Optional:-->';
					
					$thisAuthorDeptIds = array();
					
					$person = checkPerson(array("kaustid" => "no known id", "orcid" => $orcid, "name" => $name, "scopusid" => "no known id"));
					
					addInternalPerson($person);		
					
					if(!empty($person['kaustID']))
					{
						$thisAuthorDeptIds = checkDeptIDs($person, $pubdate);
						$allAuthorsDeptIds = array_merge($allAuthorsDeptIds, $thisAuthorDeptIds);					
					}
					
					//Add KAUST depts for internal persons
					if(!empty($thisAuthorDeptIds))
					{
						$purexml .= '<!--Optional:-->
						<v1:organisations>
						<!--1 or more repetitions:-->';
						
						addKaustDeptAffs($orgunitnumbers, $thisAuthorDeptIds, '');
						
						$purexml .= '</v1:organisations>';
					}
					
					$purexml .= '</v1:author>';
				}
				$purexml .= '</v1:persons>';
							
				//Add owning unit id
				owner($allAuthorsDeptIds, $ownerid);
														
				urls($handle, $urls);
							
				electronicVersions('', $files, '');
				
				notes($collection, $kaustGrantNumbers, $acknowledgement);
				
				visibility();
				
				externalIds($handle, '', '', '', '', '');
				
				if(!empty($patentnumbers[0]))
				{
					$purexml .= '<v1:patentNumber>'.$patentnumbers[0].'</v1:patentNumber>';
				}
				elseif(!empty($applicationnumbers[0]))
				{
					$purexml .= '<v1:patentNumber>'.$applicationnumbers[0].'</v1:patentNumber>';
				}
				
				/* if(!empty($applicationnumbers))
				{
					foreach($applicationnumbers as $applicationnumber)
					{						
						$purexml .= '<v1:priorityNumber>'.$applicationnumber.'</v1:priorityNumber>';
					}
				} */
							
				$purexml .= '</v1:patent>';	

				ob_flush();
				flush();
				set_time_limit(0);				
			}
		}
	}	
		