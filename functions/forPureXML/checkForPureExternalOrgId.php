<?php
	//Define function to search the pureExternalOrgs tables for a matching name or scopusAffiliationID
	function checkForPureExternalOrgId($value, $type, $iteration)
	{
		global $irts, $message;
		
		$matches = array();
		$matchesName = array();
		$matchesNameAndCountry = array();
		
		if($type === 'name')
		{
			$affparts = explode(', ', $value);
			
			if(count($affparts)>1)
			{
				$country = str_replace('.', '', array_pop($affparts));
			}
			else
			{
				$country = '';
			}
			
			if($iteration === 1)
			{
				$affparts = array_unique($affparts);			
				foreach($affparts as $affname)
				{
					$affname = mysqli_real_escape_string($irts, $affname);
					$check = $irts->query("SELECT scivalID, name, country FROM repositoryAuthorityControl.pureExternalOrgs WHERE name LIKE '$affname'");
					while($checkrow = $check->fetch_assoc())
					{
						if($checkrow['country']===$country)
						{
							array_push($matchesNameAndCountry, $checkrow);
						}
						else
						{
							array_push($matchesName, $checkrow);
						}
					}
				}
			}
			elseif($iteration === 2)
			{
				foreach($scopusOrgsRows as $row)
				{
					if(strpos($value, $row['name'])!==FALSE&&$country===$row['country'])
					{
						array_push($matchesNameAndCountry, $row);
					}
					elseif(strpos($value, $row['name'])!==FALSE)
					{
						array_push($matchesName, $row);
					}
				}
			}
		}	
		elseif($type === 'scopusid')
		{
			$check = $irts->query("SELECT scivalID, name, country FROM repositoryAuthorityControl.pureExternalOrgs LEFT JOIN repositoryAuthorityControl.pureExternalOrgsScopusIDs USING(scivalID) WHERE scopusAffiliationID LIKE '$value'");
			while($checkrow = $check->fetch_assoc())
			{
				array_push($matches, $checkrow);
			}
		}				

		if(!empty($matchesNameAndCountry))
		{
			$matches = $matchesNameAndCountry;
		}
		elseif(!empty($matchesName))
		{
			$matches = $matchesName;
		}		
			
		if(count($matches)===1)
		{
			$match = $matches[0];
		}
		elseif(count($matches)>1)
		{
			$match = array();
			$message .= '<br> - More than one Pure External Org Match By '.$type.' for: '.$value;
			$i = 1;
			foreach($matches as $mismatch)
			{
				$message .= '<br> - Match '.$i.') '.$mismatch['name'].' - scivalID: '.$mismatch['scivalID'].' - country: '.$mismatch['country']; 
				$i++;
			}
			if($type === 'scopusid'&&$iteration === 2)
			{
				$match = $matches[0];
				$message .= '<br> - The first scopus ID based match ('.$match['name'].' - scivalID: '.$match['scivalID'].' - country: '.$match['country'].' will be used...';				
			}
		}
		else
		{
			$message .= '<br> - No Pure External Org Match By '.$type.' for: '.$value;
			$match = array();
		}
		
		return $match;
	}	
