<?php	
/*

**** This file is responsible for adding a new entry to IRTS for processing if there is no existing matching entry.

** Parameters :
	$source : name of the source system.
	$idInSource : id of this record in the source system.
	$idInSourceField : standard field name in the format namespace.element.qualifier .
	$checkCrossref : TRUE for publication services where we expect Crossref DOIs in the metadata, FALSE for other sources and for Crossref itself.
	$harvestBasis : the reason this item was harvested, to be displayed to reviewer during processing
	
** Output : returns the status of the entry (existing, inProcess).

*/

//------------------------------------------------------------------------------------------------------------

	function addToProcess($source, $idInSource, $idInSourceField, $checkCrossref, $harvestBasis = NULL)
	{
		global $irts;

		$idInIRTS = '';
		
		$type = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.type'), array('value'), 'singleValue');
		
		$doi = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.identifier.doi'), array('value'), 'singleValue');
		
		$title = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.title'), array('value'), 'singleValue');
		
		$date = getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, 'dc.date.issued'), array('value'), 'singleValue');
		
		//flag will be changed to FALSE if one of the checks for existing records returns results
		$addToProcess = TRUE;
		
		//for sources where most records are expected to have a corresponding Crossref DOI
		if($checkCrossref)
		{
			if(empty($doi))
			{
				$authors = implode('; ', getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, "dc.contributor.author"), array('value'), 'arrayOfValues'));

				$doi = retrieveCrossrefDOIByCitation($title, $authors);
			}
			
			if(!empty($doi))
			{
				$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $report, 'crossref');

				if(empty($existingRecords))
				{
					if(identifyRegistrationAgencyForDOI($doi, $report)==='crossref')
					{
						$sourceData = retrieveCrossrefMetadataByDOI($doi, $report);

						if(!empty($sourceData))
						{
							$recordType = processCrossrefRecord($sourceData, $report);
						}
					}
				}
			}
		}
		
		//check for existing repository records (repository is default source to check)
		$existingRecords = checkForExistingRecords($idInSource, $idInSourceField, $report);

		if(!empty($existingRecords))
		{
			$addToProcess = FALSE;
		}
		
		if(!empty($doi)&&$idInSourceField!=='dc.identifier.doi')
		{
			$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $report);

			if(!empty($existingRecords))
			{
				$addToProcess = FALSE;
			}
			
			//Check for existing IRTS entry based on DOI
			$existingRecords = checkForExistingRecords($doi, 'dc.identifier.doi', $report, 'irts');

			if(!empty($existingRecords))
			{
				$addToProcess = FALSE;
			}
		}
		else
		{
			//Some titles may have quotation marks or apostrophes that need to be escaped before inclusion in a MySQL query
			$title = $irts->real_escape_string($title);
			
			//Title check for non-DOI items, only reports match if item type is also the same...
			$existingRecords = getValues($irts, "SELECT `idInSource` FROM `metadata` 
				WHERE source IN ('irts','repository') 
				AND	field LIKE 'dc.title' 
				AND value LIKE '$title' 
				AND idInSource IN ( SELECT `idInSource` FROM `metadata` 
					WHERE source IN ('irts','repository') 
					AND	field LIKE 'dc.type' 
					AND value LIKE '$type'
				)", array('idInSource'), 'arrayOfValues');

			if(!empty($existingRecords))
			{
				$addToProcess = FALSE;
			}
		}
		
		//Check for existing IRTS entry based on current source and id (with status entry) OR based on standard id field from any source 
		$existingRecords = getValues($irts, "SELECT `idInSource` FROM `metadata` WHERE source LIKE 'irts' AND (
				(field = 'irts.source' AND value LIKE '$source' AND deleted IS NULL AND rowID IN (
					SELECT `parentRowID` FROM `metadata` WHERE source LIKE 'irts' AND field = 'irts.idInSource' AND value LIKE '$idInSource' AND deleted IS NULL)) 
				OR 
				(field = '$idInSourceField' AND value LIKE '$idInSource' AND deleted IS NULL)
			)", array('idInSource'), 'arrayOfValues');

		if(!empty($existingRecords))
		{
			$addToProcess = FALSE;
		}
		
		if($addToProcess)
		{
			$status = 'inProcess';
			
			$idInIRTS = generateNewID($source);
			
			$result = saveValue('irts', $idInIRTS, 'irts.source', 1, $source, NULL);
			
			$parentRowID =  $result['rowID'];
				
			$result = saveValue('irts', $idInIRTS, 'irts.idInSource', 1, $idInSource, $parentRowID);

			$result = saveValue('irts', $idInIRTS, 'dc.type', 1, $type, NULL);

			$result = saveValue('irts', $idInIRTS, 'irts.status', 1, $status, NULL);

			$result = saveValue('irts', $idInIRTS, $idInSourceField, 1, $idInSource, NULL);
			
			$result = saveValue('irts', $idInIRTS, 'dc.title', 1, $title, NULL);
			
			$result = saveValue('irts', $idInIRTS, 'dc.date.issued', 1, $date, NULL);
			
			if(!empty($doi)&&$idInSourceField!=='dc.identifier.doi')
			{
				$result = saveValue('irts', $idInIRTS, 'dc.identifier.doi', 1, $doi, NULL);
			}
			
			if(is_string($harvestBasis))
			{
				$result = saveValue('irts', $idInIRTS, 'irts.harvest.basis', 1, $harvestBasis, NULL);
			}
		}
		else
		{	
			$status = 'existing';
		}

		return array('status'=>$status, 'idInIRTS'=>$idInIRTS);
	}	
