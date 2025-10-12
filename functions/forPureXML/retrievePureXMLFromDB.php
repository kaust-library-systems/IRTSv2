<?php

/*

**** This file is responsible for retrieving the XML from the DB for all of the items in a given export.

** Parameters :
	No parameters required


** Created by : Yasmeen alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 16 December 2019- 9:00 AM

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function retrievePureXMLFromDB($exportID, $location)
{
	//Database
	global $irts;

	//included files may occassionally introduce whitespace into the output buffer, this must be removed before starting to output the XML
	ob_clean();
	
	// init
	$output = fopen($location, 'w');

	$itemType = explode('_', $exportID)[1];

	$specialTypes = array('All ETDs'=>'studentTheses', 'Thesis'=>'studentTheses', 'Dissertation'=>'studentTheses', 'Dataset'=>'datasets');

	$namespaces = array('studentTheses'=>'v1.studentthesis-sync.pure.atira.dk', 'datasets'=>'v1.dataset.pure.atira.dk');

	if(in_array($itemType, array_keys($specialTypes)))
	{
		$result = fwrite($output, '<?xml version="1.0" encoding="UTF-8"?><v1:'.$specialTypes[$itemType].' xmlns:v1="'.$namespaces[$specialTypes[$itemType]].'" xmlns:v3="v3.commons.pure.atira.dk">');
	}
	else
	{
		$result = fwrite($output, '<?xml version="1.0" encoding="UTF-8"?><v1:publications xmlns:v1="v1.publication-import.base-uk.pure.atira.dk" xmlns:v3="v3.commons.pure.atira.dk">');
	}

	if($exportID == 'facultyAuthorIDs_All')
	{
		$recordIDs = array();

		$facultyAuthorIDs = getValues($irts, "SELECT value
			FROM metadata id
			WHERE id.source = 'local'
			AND id.field = 'dc.identifier.scopusid'
			AND id.idInSource IN (SELECT idInSource
				FROM metadata
				WHERE source = 'local'
				AND field = 'local.person.title'
				AND (
					value LIKE '%prof %'
					OR value LIKE '%prof.%'
					OR value LIKE '%professor%'
				)
				AND value NOT LIKE '%Visiting%'
				AND deleted IS NULL
			)
			AND id.value IN (SELECT SUBSTRING_INDEX(idInSource,'_',-1)
				FROM `sourceData`
				WHERE `source` = 'forPure_scopusExport'
				AND `idInSource` LIKE 'authorID%'
				AND `deleted` IS NULL
			)
			AND id.deleted IS NULL", array('value'), 'arrayOfValues');

		foreach($facultyAuthorIDs as $facultyAuthorID)
		{
			$scopusItemList =  getValues($irts, "SELECT sourceData FROM `sourceData` WHERE source = 'forPure_scopusExport' AND `idInSource` LIKE 'authorID_$facultyAuthorID' AND format = 'JSON' AND `deleted` IS NULL", array('sourceData'), 'singleValue');

			$recordIDs = array_merge($recordIDs, json_decode($scopusItemList, TRUE));
		}
		$recordIDs = array_unique($recordIDs);
	}
	elseif(strpos($exportID, '_NewAndModified_Range')!==FALSE)
	{
		$recordIDs = array();

		$startDate = new DateTime(explode('_', $exportID)[4]);
		$endDate = new DateTime(explode('_', $exportID)[5]);
		$endDate = $endDate->modify('+1 day');

		$interval = new DateInterval('P1D');
		$daterange = new DatePeriod($startDate, $interval, $endDate);

		foreach($daterange as $date)
		{
			$added = $date->format("Y-m-d");
			
			$jsonData = getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'JSON' AND `idInSource` LIKE '".explode('_NewAndModified',$exportID)[0].'_New'."' AND `added` LIKE '$added%' ORDER BY `added` DESC LIMIT 1", array('sourceData'), 'singleValue');

			if(!empty($jsonData))
			{
				//Add new item record IDs
				$recordIDs = array_merge($recordIDs, json_decode($jsonData, TRUE));
			}

			$jsonData = getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'JSON' AND `idInSource` LIKE '".explode('_NewAndModified',$exportID)[0].'_Modified'."' AND `added` LIKE '$added%' ORDER BY `added` DESC LIMIT 1", array('sourceData'), 'singleValue');

			if(!empty($jsonData))
			{
				//Add modified item record IDs
				$recordIDs = array_merge($recordIDs, json_decode($jsonData, TRUE));
			}
		}		
		
		$recordIDs = array_unique($recordIDs);
	}
	elseif(strpos($exportID, '_NewAndModified')!==FALSE)
	{
		$recordIDs = array();

		$jsonData =  getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'JSON' AND `idInSource` LIKE '".str_replace('_NewAndModified', '_New', $exportID)."' AND `deleted` IS NULL ORDER BY `added` DESC LIMIT 1",array('sourceData'), 'singleValue');

		//Add new item record IDs
		$recordIDs = array_merge($recordIDs, json_decode($jsonData, TRUE));

		$jsonData =  getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'JSON' AND `idInSource` LIKE '".str_replace('_NewAndModified', '_Modified', $exportID)."' AND `deleted` IS NULL ORDER BY `added` DESC LIMIT 1",array('sourceData'), 'singleValue');

		//split the data
		$recordIDs = array_merge($recordIDs, json_decode($jsonData, TRUE));
	}
	elseif(strpos($exportID, 'authorID')!==FALSE)
	{
		$jsonData =  getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_scopusExport' AND `format` = 'JSON' AND `idInSource` LIKE '$exportID' AND `deleted` IS NULL ORDER BY `added` DESC LIMIT 1",array('sourceData'), 'singleValue');

		//split the data
		$recordIDs = json_decode($jsonData, TRUE);
	}
	else
	{
		$jsonData =  getValues($irts, "SELECT sourceData FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'JSON' AND `idInSource` LIKE '$exportID' AND `deleted` IS NULL ORDER BY `added` DESC LIMIT 1",array('sourceData'), 'singleValue');

		//split the data
		$recordIDs = json_decode($jsonData, TRUE);
	}

	foreach ($recordIDs as $recordID)
	{
		if(strpos($recordID, '10754')===0) //repository handles have the prefix 10754
		{
			$itemxml = trim(getValues($irts, "SELECT `sourceData` FROM `sourceData` WHERE `source` = 'forPure_repositoryExport' AND `format` = 'XML' AND `idInSource` LIKE '$recordID' AND `deleted` IS NULL",array('sourceData'), 'singleValue'));
			
			$itemxml = str_replace('{bibliographicalNotePlaceholder}', 'KAUST Repository Item: Exported on '.TODAY, $itemxml);
		}
		elseif(strpos($recordID, '2')===0) //scopus EIDs all start with 2
		{
			$itemxml = trim(getValues($irts, "SELECT `sourceData` FROM `sourceData` WHERE `source` = 'forPure_scopusExport' AND `format` = 'XML' AND `idInSource` LIKE '$recordID' AND `deleted` IS NULL",array('sourceData'), 'singleValue'));
			
			$itemxml = str_replace('{bibliographicalNotePlaceholder}', 'Generated from Scopus record by KAUST IRTS on '.TODAY, $itemxml);
		}

		$result = fwrite($output, PHP_EOL.trim(preg_replace('/\t+/', '', $itemxml)));
	}

	if(in_array($itemType, array_keys($specialTypes)))
	{
		$result = fwrite($output, '</v1:'.$specialTypes[$itemType].'>');
	}
	else
	{
		$result = fwrite($output, '</v1:publications>');
	}

	fclose($output);
}
