<?php
/*

**** This file is responsible for listing the external sources that also have a record for this item.

** Parameters :
	No parameters required
	
** Return:
	output: associated array 

*/

//-------------------------------------------------------------------------------------------------------------------------------------------------- 

function metadataSourceRecords($mode, $from){
	
	#init 
	global $irts, $repository;

	$report = '';
	$errors = array();
	$recordTypeCounts = [
		'all' => 0,
		'metadataSourceRecords Rows Added' => 0
	];

	if($mode == 'replaceAll')
	{
		//delete all rows
		$result = $repository->query("TRUNCATE TABLE metadataSourceRecords");		
	}
	elseif($from === NULL) {
		//last row modified date
		$from = getValues($repository, "SELECT MAX(`Row Modified`) AS lastRowModified FROM `metadataSourceRecords` WHERE 1", array('lastRowModified'), 'singleValue');
	}

	$columns = array(
		'Source', 
		'ID in Source', 
		'Type in Source',
		'Title in Source',
		'Publication Date in Source',
		'Publication Year in Source',
		'Citation Count',
		'Has KAUST Affiliation in Source',
		'Has KAUST Acknowledgement in Source',
		'DOI',
		'Has DOI',
		'Handle',
		'Has Repository Record', 
		'ID in IRTS',
		'Has IRTS Process Entry',
		'First Source',
		'Only Source', 
		'Date of First Harvest', 
		'Date of Last Update',
		'Row Modified'
	);

	$sourceIDFields = [
		'arxiv' => 'dc.identifier.arxivid', 
		'crossref' => 'dc.identifier.doi', 
		'datacite' => 'dc.identifier.doi', 
		'europePMC' => 'dc.identifier.pmcid', 
		'ieee' => 'dc.identifier.doi', 
		'github' => 'dc.identifier.github', 
		'googleScholar' => 'googleScholar.cluster.id', 
		'ncbi' => 'dc.identifier.bioproject', 
		'pubmed' => 'dc.identifier.pmid', 
		'scopus' => 'dc.identifier.eid', 
		'semanticScholar' => 'dc.identifier.semanticScholarPaperID', 
		'wos' => 'dc.identifier.wosut'
	];
	
	$sourceCitationFields = [
		'scopus' => 'scopus.coredata.citedby-count',
		'semanticScholar' => 'semanticScholar.citationCount'
	];

	foreach($sourceIDFields as $source => $sourceIDField){
		$baseQuery = "SELECT DISTINCT `source`, `idInSource` 
			FROM `metadata` 
			WHERE source = '$source' 
			AND idInSource NOT LIKE 'member_%' 
			";
		
		if(!empty($from)){
			$report .= "Starting from $from".PHP_EOL;
	
			$baseQuery .= " AND (
				`added` >= '$from'
				OR
				`deleted` >= '$from')";
		}
		else{
			$baseQuery .= " AND `deleted` IS NULL";
		}
		
		$sourceRecords = getValues($irts, $baseQuery, array('idInSource'), 'arrayOfValues');

		foreach($sourceRecords as $idInSource){
			$recordTypeCounts['all']++;

			//check for existing row by idInSource and delete if it exists
			$result = $repository->query("DELETE FROM metadataSourceRecords WHERE `source` = '$source' AND `ID in Source` = '$idInSource'");

			$row = array();

			$row['Source'] = $source;
			$row['ID in Source'] = $idInSource;
			$row['Type in Source'] = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.type' AND `deleted` IS NULL", array('value'), 'singleValue');
			$row['Title in Source'] = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.title' AND `deleted` IS NULL", array('value'), 'singleValue');
			$row['Publication Date in Source'] = substr(getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.date.issued' AND `deleted` IS NULL", array('value'), 'singleValue'), 0, 10);
			$row['Publication Year in Source'] = substr(explode('-', $row['Publication Date in Source'])[0], 0, 4);
			
			if (in_array($source, array_keys($sourceCitationFields))) {
			    $citationCount = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = '". $sourceCitationFields[$source] ."' AND `deleted` IS NULL", array('value'), 'singleValue');
			    
			    // Ensure the value is either a valid integer or NULL
			    $row['Citation Count'] = is_numeric($citationCount) ? (int)$citationCount : NULL;
			} else {
			    $row['Citation Count'] = NULL;
			}

			// check if the item has a KAUST affiliation in the source record
			$affiliations = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.contributor.affiliation' AND `deleted` IS NULL", array('value'), 'arrayOfValues');

			$kaustAffiliation = 'No';
			foreach($affiliations as $affiliation){
				if(institutionNameInString($affiliation)){
					$kaustAffiliation = 'Yes';
					break;
				}
			}

			$row['Has KAUST Affiliation in Source'] = $kaustAffiliation;

			// check if the item has a KAUST acknowledgement in the source record
			$acknowledgements = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.description.acknowledgement' AND `deleted` IS NULL", array('value'), 'arrayOfValues');

			$kaustAcknowledgement = 'No';
			foreach($acknowledgements as $acknowledgement){
				if(institutionNameInString($acknowledgement)){
					$kaustAcknowledgement = 'Yes';
					break;
				}
			}

			$row['Has KAUST Acknowledgement in Source'] = $kaustAcknowledgement;

			$row['DOI'] = getValues($irts, "SELECT `value` FROM `metadata` WHERE source = '$source' AND `idInSource` = '$idInSource' AND `field` = 'dc.identifier.doi' AND `deleted` IS NULL", array('value'), 'singleValue');

			if(!empty($row['DOI'])){
				$row['Has DOI'] = 'Yes';

				$handle = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source = 'repository' AND `field` = 'dc.identifier.doi' AND `value` = '".$row['DOI']."' AND `deleted` IS NULL", array('idInSource'), 'singleValue');
			}
			else{
				$row['Has DOI'] = 'No';

				$handle = getValues(
					$irts, 
					"SELECT `idInSource` FROM `metadata` WHERE source = 'repository' 
						AND `field` = '$sourceIDField'
						AND `value` = '$idInSource' 
						AND `deleted` IS NULL", 
					array('idInSource'),
					'singleValue');
			}

			$row['Handle'] = $handle;

			if(!empty($handle)){
				$row['Has Repository Record'] = 'Yes';
			}
			else{
				$row['Has Repository Record'] = 'No';
			}

			if(!empty($doi)){
				$idInIRTS = getValues(
					$irts, 
					"SELECT `idInSource` FROM `metadata` 
						WHERE source = 'irts' 
						AND `field` = 'dc.identifier.doi' 
						AND `value` = '$doi' 
						AND `deleted` IS NULL", 
					array('idInSource'), 
					'singleValue');
			}
			else{
				$idInIRTS = getValues($irts,
					"SELECT `idInSource` FROM `metadata` 
						WHERE source = 'irts' 
						AND `field` = '$sourceIDField'
						AND `value` = '$idInSource'
						AND `deleted` IS NULL",
					array('idInSource'),
					'singleValue');
			}

			$row['ID in IRTS'] = $idInIRTS;

			$row['Has IRTS Process Entry'] = !empty($idInIRTS) ? 'Yes' : 'No';

			// check if this is the first source for the record in IRTS
			$firstSource = getValues(
				$irts, 
				"SELECT `value` FROM `metadata` 
					WHERE source = 'irts' 
					AND `idInSource` = '$idInIRTS' 
					AND `field` = 'irts.source' 
					AND `deleted` IS NULL", 
				array('value'), 
				'singleValue'
			);
			
			// old records may not have a source field
			if(empty($firstSource)){
				$firstSource = $source;
			}

			$row['First Source'] = $firstSource == $source ? 'Yes' : 'No';

			// check if this is the only source for the record in IRTS
			$sourceCount = 1;

			if(!empty($doi)){
				$sourceCount = getValues(
					$irts, 
					"SELECT COUNT(DISTINCT `source`) FROM `metadata` 
						WHERE source IN ('".implode("','", array_keys($sourceIDFields))."')
						AND `field` = 'dc.identifier.doi' 
						AND `value` = '$doi' 
						AND `deleted` IS NULL", 
					array('COUNT(DISTINCT `source`)'), 
					'singleValue'
				);
			}
			
			if($sourceCount == 1){
				$title = mysqli_real_escape_string($irts, $row['Title in Source']);

				$sourceCount = getValues(
					$irts, 
					"SELECT COUNT(DISTINCT `source`) FROM `metadata` 
						WHERE source IN ('".implode("','", array_keys($sourceIDFields))."')
						AND `field` = 'dc.title'
						AND `value` = '$title' 
						AND `deleted` IS NULL", 
					array('COUNT(DISTINCT `source`)'), 
					'singleValue'
				);
			}

			if($sourceCount == 1){
				$row['Only Source'] = 'Yes';
			}
			else{
				$row['Only Source'] = 'No';
			}

			$row['Date of First Harvest'] = '';
			$row['Date of Last Update'] = '';

			if(!empty($idInIRTS)){
				$row['Row Modified'] = getValues(
					$irts, 
					"SELECT `added` FROM `metadata` 
						WHERE source = 'irts'
						AND `idInSource` = '$idInIRTS'
						AND `deleted` IS NULL
						ORDER BY `added` DESC
						LIMIT 1", 
					array('added'), 
					'singleValue'
				);
			}
			else{
				$row['Row Modified'] = getValues(
					$irts, 
					"SELECT `added` FROM `metadata` 
						WHERE source = '$source'
						AND `idInSource` = '$idInSource'
						AND `deleted` IS NULL
						ORDER BY `added` DESC
						LIMIT 1", 
					array('added'), 
					'singleValue'
				);
			}

			//insert row in metadataSourceRecords table
			if(addRow('metadataSourceRecords', $row)){
				$recordTypeCounts['metadataSourceRecords Rows Added']++;
			}
		}
	}

	return ['report' => $report, 'recordTypeCounts' => $recordTypeCounts, 'errors' => $errors];
}