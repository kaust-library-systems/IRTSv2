<?php
/*

**** This function is responsible for getting the most recent Organizations file and returning the orgs as an array.

** Returns :
	$orgsInFile : list of orgs in current file as array

*/
//-----------------------------------------------------------------------------------------------------------

function getOrgsFromFile()
{
	$report = '';
	
	//prepare arrays
	$orgsInFile = array();
	$parentOrgs = array();
	$orgLevels = array();
	$orgParents = array();

	//hard-coded preferred names for some orgs
	$preferredNames = array(
		'30000085' => 'King Abdullah University of Science and Technology (KAUST)',
		'30000284' => 'Biological and Environmental Science and Engineering',
		'30000171' => 'Computer, Electrical and Mathematical Science and Engineering',
		'30000283' => 'Physical Science and Engineering',
		'30001725' => 'Energy Resources and Petroleum Engineering',
		'30000582' => 'Applied Mathematics and Computational Science',
		'30002026' => 'Resilient Computing and Cybersecurity Center',
		'30000280' => 'Advanced Membranes and Porous Materials Center',
		'30001224' => 'Ali I. Al-Naimi Petroleum Engineering Research Center',
		'30002381' => 'Center of Excellence for Generative AI',
		'30002382' => 'Center of Excellence for Renewable Energy and Storage Technologies',
		'30002383' => 'Center of Excellence for Sustainable Food Security',
		'30002384' => 'Center of Excellence for Smart Health'
	);
	
	// create empty array to hold Organizations file names
	$orgFileNames = array();

	// create empty fileDate variable (will stay empty if no file found)
	$fileDate = '';

	// load list of files
	$fileNames = array_diff(scandir(NEW_ORG_FILE_DIRECTORY), array('..', '.'));
	
	foreach($fileNames as $fileName)
	{			
		if(strpos($fileName, 'Organizations') !== FALSE)
		{
			$orgFileNames[] = $fileName;					
		}
	}

	// Sort org files in ascending order (oldest first) as file names will be in the form Organizations{TIMESTAMP}.TXT ( for example: Organizations20240612075911.TXT )
	//sort($orgFileNames);

	// Sort org files in descending order (newest first) as file names will be in the form Organizations{TIMESTAMP}.TXT ( for example: Organizations20240612075911.TXT )
	// The old files will be left and cleaned up later by a separate retention policy script
	rsort($orgFileNames);

	// Check if there are any org files
	if(count($orgFileNames) === 0)
	{
		$report .= 'No org files found in: '.NEW_ORG_FILE_DIRECTORY.PHP_EOL;
	}
	else
	{
		// Get the first file in the list (the newest org unit file)
		$currentOrgFileName = $orgFileNames[0];

		$report .= PHP_EOL.'Name of current file:'.$currentOrgFileName.PHP_EOL;

		// Get the date of the file in the format YYYY-MM-DD from the file name
		$fileDateStamp = str_replace('Organizations','',$currentOrgFileName);

		$fileDate = substr($fileDateStamp, 0, 4).'-'.substr($fileDateStamp, 4, 2).'-'.substr($fileDateStamp, 6, 2);

		$currentOrgFilePath = NEW_ORG_FILE_DIRECTORY.$currentOrgFileName;
		$archivedFilePath = OLD_ORG_FILE_DIRECTORY.$currentOrgFileName;

		$currentOrgFile = fopen($currentOrgFilePath, "r");

		//get the first row (column names) and trim any hanging whitespace
		$columnNames = array_map('trim', fgetcsv($currentOrgFile, 0, "|"));

		//print_r($columnNames);
		
		while(($row = fgetcsv($currentOrgFile, 0, "|")) !== FALSE)
		{
			$row = array_combine($columnNames, $row);
			
			//top level unit will have the id of the university
			if($row['Id'] === '1001')
			{
				$row['Id'] = '30000085';
			}

			$orgID = $row['Id'];

			//Use hard-coded preferred name if available
			if(in_array($orgID, array_keys($preferredNames)))
			{
				//$report .= 'Preferred name used for org '.$orgID.': '.$preferredNames[$orgID].' - instead of: '.$row['Name'].PHP_EOL;

				$row['Name'] = $preferredNames[$orgID];
			}

			$orgsInFile[$orgID] = $row;

			$parentOrgs[$orgID] = $row['Parent Organization Id'];
		}

		fclose($currentOrgFile);

		// calculate the level of each org (this will be used for setting the org type)
		foreach($parentOrgs as $orgID => $parentOrgID)
		{
			$parents = [];
			$parents[] = $parentOrgID;
			$level = 1;

			//top level unit will have no parent
			while($parentOrgID !== '')
			{
				if(isset($parentOrgs[$parentOrgID]))
				{
					$parentOrgID = $parentOrgs[$parentOrgID];
					$parents[] = $parentOrgID;
					$level++;
				}
			}

			$orgsInFile[$orgID]['Org Type'] = setOrgType($orgsInFile[$orgID], $level, $parents);
		}

		// go through orgs in file and transform so that field names match and calculated fields (org type and visibility) are added
		foreach($orgsInFile as $orgID => $orgInFile)
		{
			$org = [];
			
			//use standard field names
			$org['local.org.id'] = $orgInFile['Id'];
			$org['local.org.name'] = $orgInFile['Name'];
			$org['local.org.parent'] = $orgInFile['Parent Organization Id'];
			$org['local.org.type'] = $orgInFile['Org Type'];

			//overwrite entry after setting standard field names and org type
			$orgsInFile[$orgID] = $org;
		}

		// copy file to archival folder (OLD_ORG_FILE_DIRECTORY) after processing
		// The old file is not removed, but will be cleaned up later by a separate retention policy script
    	if (copy($currentOrgFilePath, $archivedFilePath)) {
			$report .= "File copied successfully to: $archivedFilePath" . PHP_EOL;
		} else {
			$report .= "Error: copy() failed. File not moved to: $archivedFilePath" . PHP_EOL;
		}

		$report .= 'File date: '.$fileDate.PHP_EOL;
		$report .= 'Orgs in file: '.count($orgsInFile).PHP_EOL;
	}

	//echo $report;
	
	return ['orgsInFile' => $orgsInFile, 'fileDate' => $fileDate, 'report' => $report];
}
