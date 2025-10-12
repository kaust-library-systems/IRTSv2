<?php	
	//Define function to get common publication metadata
	function getCommonMetadata($handle)
	{
		global $repo, $pubs, $process;
		
		$meta = array();		
		
		$reporesult = $repo->query("
		SELECT `collection`, `dc.type[en]` , `dc.title[en]` , `dc.contributor.author[en]`, `dc.conference.date[en]`, `dc.conference.location[en]`, `dc.conference.name[en]`, `kaust.author[en]`, `dc.description.abstract[en]`, `dc.description.sponsorship[en]`, `dc.identifier.uri[en]`, `dc.identifier.arxivid[en]`, `dc.identifier.doi[en]`, `dc.identifier.isbn[en]`, `dc.identifier.pmcid[en]`, `dc.identifier.pmid[en]`, `originalFileURLs`, `dc.identifier.journal[en]`, `dc.subject[en]`, `dc.relation.url[en]`, `dc.rights.uri[en]`, `dc.rights[en]`, `dc.eprint.version[en]`, `dc.date.issued[en]`, `kaust.grant.number[en]`
		FROM repository
		WHERE `dc.identifier.uri[en]` = '$handle'
		AND `dc.identifier.doi[en]` != ''");
	
		if($reporesult->num_rows===1)
		{				
			$reporow = $reporesult->fetch_assoc();
			
			//Handle records with multiple DOIs
			$meta['dois'] = explode('||', $reporow['dc.identifier.doi[en]']);
			$meta['doi'] = strtolower($meta['dois'][0]);
			$doi = $meta['doi'];
			
			//echo $meta['doi'];
			
			//variables from the repository
			$meta['collection'] = $reporow['collection'];
			$meta['type'] = $reporow['dc.type[en]'];
			$meta['title'] = $reporow['dc.title[en]'];								
			$meta['abstract'] = $reporow['dc.description.abstract[en]'];
			$meta['acknowledgement'] = $reporow['dc.description.sponsorship[en]'];			
			$meta['pubdate'] = $reporow['dc.date.issued[en]'];	
			$meta['handle'] = $reporow['dc.identifier.uri[en]'];			
			
			$meta['authors'] = explode('||', $reporow['dc.contributor.author[en]']);
			$meta['kaustAuthors'] = explode('||', $reporow['kaust.author[en]']);		
			//$meta['confDate'] = htmlspecialchars($reporow['dc.conference.date[en]'], ENT_QUOTES);						
			$meta['confName'] = htmlspecialchars($reporow['dc.conference.name[en]'], ENT_QUOTES);
			$meta['confLocation'] = htmlspecialchars($reporow['dc.conference.location[en]'], ENT_QUOTES);
			$meta['journal'] = htmlspecialchars($reporow['dc.identifier.journal[en]'], ENT_QUOTES);
			$meta['urls'] = explode('||', htmlspecialchars($reporow['dc.relation.url[en]'], ENT_QUOTES));
			$meta['files'] = explode('||', $reporow['originalFileURLs']);
			$meta['version'] = $reporow['dc.eprint.version[en]'];			
			$meta['rights'] = htmlspecialchars($reporow['dc.rights[en]'], ENT_QUOTES);
			$meta['kaustGrantNumbers'] = $reporow['kaust.grant.number[en]'];
			
			$identifierTypes = array("arxivid", "isbn", "pmcid", "pmid");
			foreach($identifierTypes as $identifierType)
			{
				$meta[$identifierType] = $reporow['dc.identifier.'.$identifierType.'[en]'];
			}			
			
			if(!empty($doi))
			{
				//Retrieve fields from scopus tables
				$scopusresult = $pubs->query("
					SELECT 
					s.authorsWithControlledAffiliations authorsWithControlledScopusAffs, s.controlledAffiliations affsWithScopusIds, s.`authorsWithScopusIDs`, s.conferenceDate scopusConferenceDate, s.ISSN scopusISSN, s.Volume, s.Issue, s.articleNumber, s.startPage, s.endPage, s.DOI, s.EID, s.documentType scopusType
					FROM `scopus` s											
					WHERE s.`DOI` LIKE '$doi'");
					
				//To handle records with multiple EIDs
				$eids = array();
					
				if($scopusresult->num_rows<=1)
				{
					$scopusrow = $scopusresult->fetch_assoc();				
					$eid = str_replace('2-s2.0-', '', $scopusrow['EID']);
					array_push($eids, $eid);							
				}
				else
				{
					while($scopusrow = $scopusresult->fetch_assoc())
					{
						array_push($eids, str_replace('2-s2.0-', '', $scopusrow['EID']));

						if($scopusrow['scopusType']!=='Article in Press')
						{
							break 1;
						}
					}
				}
				$meta['eids'] = array_unique($eids);
				$meta['eid'] = $eids[0];			
				
				//variables from scopus table
				$meta['authorsWithControlledScopusAffs'] = explode('||', $scopusrow['authorsWithControlledScopusAffs']);
				$meta['authorsWithScopusIDs'] = explode('||', $scopusrow['authorsWithScopusIDs']);
				$meta['affsWithScopusIds'] = explode('||', $scopusrow['affsWithScopusIds']);
				$meta['scopusISSN'] = $scopusrow['scopusISSN'];
				$meta['volume'] = $scopusrow['Volume']; 
				$meta['issue'] = $scopusrow['Issue'];
				$meta['articleNumber'] = $scopusrow['articleNumber'];
				$meta['startPage'] = $scopusrow['startPage'];
				$meta['endPage'] = $scopusrow['endPage'];
				$meta['confDate'] = htmlspecialchars($scopusrow['scopusConferenceDate'], ENT_QUOTES);			
				
				//Retrieve fields from other tables
				$otherresult = $pubs->query("
					SELECT cr.DOI, cr.Journal crossrefJournal, cr.ISSN, cr.Publisher, w.UT
					FROM crossref cr					
					LEFT JOIN wos w
					USING ( DOI )						
					WHERE cr.`DOI` LIKE '$doi'");
				
				if($otherresult->num_rows===1)
				{				
					$otherrow = $otherresult->fetch_assoc();
						
					//variables from other tables
					if(empty($meta['journal']))
					{
						$meta['journal'] = htmlspecialchars($otherrow['crossrefJournal'], ENT_QUOTES);
					}
					$meta['issn'] = $otherrow['ISSN'];					
					$meta['ut'] = $otherrow['UT'];
							
					$meta['publisher'] = htmlspecialchars($otherrow['Publisher'], ENT_QUOTES);

					//Retrieve full author and affiliation from completed table
					$completedresult = $process->query("SELECT cA.place authorPlace, cA.value author, GROUP_CONCAT(DISTINCT cAA.value ORDER BY cAA.place DESC SEPARATOR ':aff:') affiliation FROM `harvested` h LEFT JOIN completed c ON h.id = c.harvestedID LEFT JOIN completedAuthors cA ON c.id = cA.completedID LEFT JOIN completedAuthorsAffiliations cAA ON cA.id = cAA.authorID WHERE h.DOI LIKE '$doi' GROUP BY authorPlace, author ORDER BY authorPlace");
					
					$authorsWithFullAffs = array();
					while($completedrow = $completedresult->fetch_assoc())
					{				
						array_push($authorsWithFullAffs, $completedrow);
					}
					
					$meta['authorsWithFullAffs'] = $authorsWithFullAffs;
				}
			}
		}
		//print_r($meta);
		return $meta;		
	}	
