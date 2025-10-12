<?php
	//Define function to check if an institutional name variant is in a string
	function institutionNameInString($string)
	{
		//Unique institutional affiliation strings
		$institutionNames = array("King Abdullah University of Science", "KAUST", "Thuwal", "King Abdullah University for Science", "King Abdullah Univ. of Science", "King Abdullah Univ of Science & Tech", "King Abdullah Univ Sci & Technol", "King Abdullah Uni. of Science & Tech.");
		
		//Known false positives for institutional affiliation
		$affiliationStringsToIgnore = array("Research and Development Center, Saudi Aramco, Thuwal 23955-6900, Saudi Arabia","Oil and Gas Networking Integrity Division, Research and Development Center, Saudi Aramco, Thuwal 23955-6900, Saudi Arabia","SABIC - Corporate Research and Innovation Center (CRI) at KAUST", "SABIC T and i Riyadh and CRi (KAUST), Saudi Arabia", "SABIC TandI and CRI, Riyadh and Thuwal (KAUST), Saudi Arabia", "Saudi Basic Industries Corporation (SABIC), Research Laboratories, Thuwal 23955-6900, Saudi Arabia", "SABIC Research Centres, KAUST, Riyadh, Saudi Arabia", "SABIC-CRI, KAUST, Saudi Arabia", "Chemical Catalysis, SABIC TandI (Riyadh), CRI (KAUST), Saudi Arabia", "Corporate Research and Innovation Center (CRI), KAUST, Saudi Basic Industries Corporation (SABIC), Thuwal, Saudi Arabia", "CRI, KAUST, Thuwal, Saudi Arabia", "SABIC TandI (Riyadh) and CRI (KAUST), Saudi Arabia", "SABIC, Corporate Research Institute (CRI), KAUST, Saudi Arabia", "SABIC-CRI at KAUST, Thuwal, Saudi Arabia", "Corporate Research and Innovation Center, Saudi Basic Corporation (SABIC), Thuwal 23955-6900, Saudi Arabia", "SABIC-Corporate Research and Development (CRD) at KAUST, Thuwal, Saudi Arabia", "Fundamental Catalysis, Centre for Research (CR), SABIC, KAUST, Thuwal, Saudi Arabia", "SABIC Corporate Research and Innovation Center, King Abdullah University of Science and Technology (KAUST), Thuwal, Saudi Arabia", "SABIC Corporate Research and Innovation Center; Thuwal 23955-6900 Saudi Arabia", "SABIC, Corporate Research and Innovation (KAUST), Saudi Arabia", "SABIC Corporate Research and Development Center, King Abdullah University of Science and Technology (KAUST), Thuwal, Saudi Arabia", "SABIC CRI, Fundamental Catalysis, Thuwal, Saudi Arabia", "Corporate Research and Innovation Center (CRI) at KAUST, Saudi Basic Industries Corporation (SABIC), Thuwal, Saudi Arabia");
		
		$ignore = '';
		foreach($affiliationStringsToIgnore as $affiliationStringToIgnore)
		{
			if($string === $affiliationStringToIgnore)
			{
				$ignore = 'yes';
			}
		}
		
		$matchFound = '';
		if(empty($ignore))
		{
			foreach($institutionNames as $institutionName)
			{						
				if(stripos($string, $institutionName)!==FALSE)
				{
					$matchFound = 'yes';														
				}						
			}
		}		
		
		if(!empty($matchFound))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}	
