<?php
	//Define function to generate and send repo
	function FacultyPublicationList($report)
	{
		global $irts, $repository;

        //$currentFacultyIDs = [];
       // $csvFile = fopen('/data/www/irts/bin/import/Faculty_List May_2025.csv', 'r');
        //while (($row = fgetcsv($csvFile)) !== false) {
            //$currentFacultyIDs[] = $row;
        //}
        
        //$currentFacultyIDs = array_map('str_getcsv', file('/data/www/irts/bin/import/Faculty_List May_2025.csv')); // Read from external CSV file with full file path
        $currentFacultyIDs = [];
        $year='2025';

		$facultyIDs = getValues($irts, "SELECT m.idInSource, m.parentRowID FROM `metadata` m
         WHERE `source` LIKE 'local'
		 AND field = 'local.employment.type'
		 AND value LIKE 'Faculty'
		 AND `deleted` IS NULL
		 AND parentRowID IN (
			SELECT `parentRowID` FROM metadata
			WHERE source LIKE 'local'
			AND `idInSource` = m.idInSource
			AND field = 'local.person.title'
			AND value NOT LIKE '%Instructional%'
			AND deleted IS NULL
			)", array('idInSource', 'parentRowID'), 'arrayOfValues');
				
			foreach($facultyIDs as $faculty)
			{
				$idInSource = $faculty['idInSource'];
				$parentRowID = $faculty['parentRowID'];
					
				$endedThisYearOrBefore = getValues($irts, "SELECT `parentRowID` FROM metadata
					WHERE source LIKE 'local'
					AND `parentRowID` = '$parentRowID'
					AND field = 'local.date.end'
					AND (value < '$year' OR value LIKE '$year%')
					AND deleted IS NULL", array('parentRowID'), 'singleValue');
						
				$startedThisYearOrBefore = getValues($irts, "SELECT `parentRowID` FROM metadata
					WHERE source LIKE 'local'
					AND `parentRowID` = '$parentRowID'
					AND field = 'local.date.start'
					AND (value < '$year' OR value LIKE '$year%')
					AND deleted IS NULL", array('parentRowID'), 'singleValue');
						
				if(empty($endedThisYearOrBefore) && !empty($startedThisYearOrBefore))
				{
					$currentFacultyIDs[] = $idInSource;
				}
            }

 
        //remove duplicates from array
        $currentFacultyIDs = array_unique($currentFacultyIDs);
             $fileHandle = fopen('/data/www/irts/bin/export/faculty_publications.csv', 'a');
             fputcsv($fileHandle, ['KAUST ID', 'Name','Scopus ID','RepositoryItemHandle', 'Type', 'Title', 'DOI','EID','Reposittory Issued Date', 'published-online date']);
        foreach($currentFacultyIDs as $facultyID)
        {
            echo $facultyID."\n";
            $controlName = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'local.person.name'), array('value'), 'singleValue');
            $orcid = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'dc.identifier.orcid'), array('value'), 'singleValue');
            $scopusid = getValues($irts, setSourceMetadataQuery('local', $facultyID, NULL, 'dc.identifier.scopusid'), array('value'), 'singleValue');

            //Set query for list of all items with this author
            $allItemsQueries = setIndividualPublicationListQueries($controlName);
            $allItems = getValues($irts, $allItemsQueries['mysqlQuery'], array('idInSource'), 'arrayOfValues');
            // add the header row
        
        
        

            foreach($allItems  as $itemHandle)
            {
                
                $itemType = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.type'), array('value'), 'singleValue');
                $itemTitle = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.title'), array('value'), 'singleValue');
                $itemDate = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.date.issued'), array('value'), 'singleValue');
                $itemDOI = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.identifier.doi'), array('value'), 'singleValue');
                $itemPublishedDate = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.date.published-online'), array('value'), 'singleValue');
                $itemEID = getValues($irts, setSourceMetadataQuery('repository', $itemHandle, NULL, 'dc.identifier.eid'), array('value'), 'singleValue');
                //write to csv 
                // Only write to CSV if the item has a DOI
                if (!empty($itemDOI) || !empty($itemEID)) 
                {
                   
                    fputcsv($fileHandle, [$facultyID, $controlName ,$scopusid, $itemHandle, $itemType, $itemTitle, $itemDOI, $itemEID, $itemDate, $itemPublishedDate]);
                }
            }

        }
        // Close the CSV file
        fclose($fileHandle);
    }





