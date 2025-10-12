<?php
	//Define function to add an external affiliation
	function addExternalAff($aff, $affsWithScopusIds)
	{
		global $purexml, $message, $scopusOrgsRows;
		
		$match = '';
		if(!empty($affsWithScopusIds))
		{
			foreach($affsWithScopusIds as $affwithid)
			{
				if(strpos($affwithid, '::afid:')!==FALSE)
				{
					$affwithidparts = explode('::afid:', $affwithid);
					$affwithidaff = $affwithidparts[0];														
					if($aff === $affwithidaff)
					{
						$affid = $affwithidparts[1];
						
						$match = checkForPureExternalOrgId($affid, 'scopusid', 1);
					}
				}
			}
		}
		
		//Break the full aff into parts and see if one part is an exact match for a Scival Org name
		if(empty($match))
		{			
			$match = checkForPureExternalOrgId($aff, 'name', 1);
		}	
		
		//Search within the full aff for Scival Org names
		if(empty($match))
		{
			$match = checkForPureExternalOrgId($aff, 'name', 2);
		}
		
		//If there are no unique matches, just use the first scopus id match (this replicates what would happen upon import to Pure of an item with a scopus id that matched more than one External Org with Scival ID in Pure
		if(empty($match)&&!empty($affid))
		{
			$match = checkForPureExternalOrgId($affid, 'scopusid', 2);
		}
		
		if(!empty($match))
		{
			$purexml .= '<v1:organisation id="'.$match['scivalID'].'">
			<v1:name>
			<!--1 or more repetitions:-->
			<commons:text lang="en" country="US">'.htmlspecialchars($match['name'], ENT_QUOTES).'</commons:text>
			</v1:name></v1:organisation>';
		}
		elseif(empty($match)&!empty($affid))
		{
			$message .= '<br> - Affiliation entry made with scopus id as no Scival ID was found for: '.$aff.' - '.$affid;
			
			$purexml .= '<v1:organisation id="'.$affid.'">
			<v1:name>
			<!--1 or more repetitions:-->
			<commons:text lang="en" country="US">'.htmlspecialchars($aff, ENT_QUOTES).'</commons:text>
			</v1:name></v1:organisation>';
		}
		else
		{
			$message .= '<br> - Affiliation entry made with no id for: '.$aff;
			
			$purexml .= '<v1:organisation>
			<v1:name>
			<!--1 or more repetitions:-->
			<commons:text lang="en" country="US">'.htmlspecialchars($aff, ENT_QUOTES).'</commons:text>
			</v1:name></v1:organisation>';
		}
	}	
