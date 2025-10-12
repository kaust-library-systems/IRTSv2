<?php
	//Define functions for patent handling
	
	//Function to change from 10 digit to 11 digit US application number
	function lengthenUSApplicationNumber($numberToCheck)
	{
		if(strpos($numberToCheck, 'US')!==FALSE)
		{
			$numberParts = explode(' ', $numberToCheck);
			$countryCode = $numberParts[0];
			$patentNumber = $numberParts[1];
			$kindCode = $numberParts[2];
			if(strlen($patentNumber)===10)
			{
				$part1 = substr($patentNumber, 0, 4);
				$part2 = substr($patentNumber, 4);
				
				$patentNumber = $part1 . '0' . $part2;
				$numberToCheck = $countryCode . ' ' . $patentNumber . ' ' . $kindCode;
			}
		}
		return $numberToCheck;
	}
	
	//Function to change from 11 digit to 10 digit US application number
	function shortenUSApplicationNumber(&$numberToCheck)
	{
		if(strpos($numberToCheck, 'US')!==FALSE)
		{
			$numberParts = explode(' ', $numberToCheck);
			$countryCode = $numberParts[0];
			$patentNumber = $numberParts[1];
			$kindCode = $numberParts[2];
			if(strlen($patentNumber)===11)
			{
				$part1 = substr($patentNumber, 0, 4);
				$part2 = substr($patentNumber, 5);
				
				$patentNumber = $part1 . $part2;
				$numberToCheck = $countryCode . ' ' . $patentNumber . ' ' . $kindCode;
			}
		}
	}
	
	//Function to change from Derwent format to base format
	function derwentToUniversal(&$numberToCheck)
	{
		$numberParts = explode('-', $numberToCheck);
		$countryCode = substr($numberParts[0], 0, 2);
		$patentNumber = substr($numberParts[0], 2);
		
		if(!empty($numberParts[1]))
		{
			$kindCode = $numberParts[1];
		}
		else
		{
			$kindCode = '';
		}
		$numberToCheck = $countryCode . ' ' . $patentNumber . ' ' . $kindCode;
	}
	
	//Function to change from Google Patents format to base format
	function googlePatentsToUniversal($numberToCheck)
	{
		if(strpos($numberToCheck, ' ')===FALSE)
		{
			if(preg_match("/\A[a-zA-Z]{2}/", $numberToCheck))
			{
				$countryCode = substr($numberToCheck, 0, 2);
				$patentNumber = substr($numberToCheck, 2, -2);
				$kindCode = substr($numberToCheck, -2);
				
				$numberToCheck = $countryCode . ' ' . $patentNumber . ' ' . $kindCode;
			}
		}
		return $numberToCheck;
	}
	
	//Function to convert country codes to names
	function countryCodeToName($numberToCheck, &$countries)
	{
		$countryCodesAndNames = array(
			"AL" => "Albania",
			"AP" => "African Regional Industrial Property Organization",
			"AR" => "Argentina",
			"AT" => "Austria",
			"AU" => "Australia",
			"BA" => "Bosnia and Herzegovina",
			"BE" => "Belgium",
			"BG" => "Bulgaria",
			"BR" => "Brazil",
			"CA" => "Canada",
			"CH" => "Switzerland",
			"CL" => "Chile",
			"CN" => "China",
			"CO" => "Colombia",
			"CR" => "Costa Rica",
			"CS" => "Czechoslovakia (up to 1993)",
			"CU" => "Cuba",
			"CY" => "Cyprus",
			"CZ" => "Czech Republic",
			"DD" => "German Democratic Republic",
			"DE" => "Germany",
			"DK" => "Denmark",
			"DZ" => "Algeria",
			"EA" => "Eurasian Patent Organization",
			"EC" => "Ecuador",
			"EE" => "Estonia",
			"EG" => "Egypt",
			"EP" => "European Patent Office",
			"ES" => "Spain",
			"FI" => "Finland",
			"FR" => "France",
			"GB" => "United Kingdom",
			"GC" => "Gulf Cooperation Council",
			"GE" => "Georgia",
			"GR" => "Greece",
			"GT" => "Guatemala",
			"HK" => "Hong Kong (S.A.R.)",
			"HR" => "Croatia",
			"HU" => "Hungary",
			"ID" => "Indonesia",
			"IE" => "Ireland",
			"IL" => "Israel",
			"IN" => "India",
			"IS" => "Iceland",
			"IT" => "Italy",
			"JP" => "Japan",
			"KE" => "Kenya",
			"KR" => "Korea (South)",
			"LI" => "Liechtenstein",
			"LT" => "Lithuania",
			"LU" => "Luxembourg",
			"LV" => "Latvia",
			"MA" => "Morocco",
			"MC" => "Monaco",
			"MD" => "Republic of Moldova",
			"ME" => "Montenegro",
			"MK" => "Former Yugoslav Republic of Macedonia",
			"MN" => "Mongolia",
			"MT" => "Malta",
			"MW" => "Malawi",
			"MX" => "Mexico",
			"MY" => "Malaysia",
			"NC" => "New Caledonia",
			"NI" => "Nicaragua",
			"NL" => "Netherlands",
			"NO" => "Norway",
			"NZ" => "New Zealand",
			"OA" => "African Intellectual Property Organization",
			"PA" => "Panama",
			"PE" => "Peru",
			"PH" => "Philippines",
			"PL" => "Poland",
			"PT" => "Portugal",
			"RO" => "Romania",
			"RS" => "Serbia",
			"RU" => "Russian Federation",
			"SE" => "Sweden",
			"SG" => "Singapore",
			"SI" => "Slovenia",
			"SK" => "Slovakia",
			"SM" => "San Marino",
			"SU" => "Soviet Union (USSR)",
			"SV" => "El Salvador",
			"TJ" => "Tajikistan",
			"TR" => "Turkey",
			"TT" => "Trinidad and Tobago",
			"TW" => "Taiwan",
			"UA" => "Ukraine",
			"US" => "United States of America",
			"UY" => "Uruguay",
			"VN" => "Viet Nam",
			"WO" => "World Intellectual Property Organization (WIPO)",
			"YU" => "Yugoslavia/Serbia and Montenegro",
			"ZA" => "South Africa",
			"ZM" => "Zambia",
			"ZW" => "Zimbabwe"
		);
		
		$countryCode = substr($numberToCheck, 0, 2);	
		array_push($countries, $countryCodesAndNames[$countryCode]);
	}
