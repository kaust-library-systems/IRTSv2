<?php
	/*

	**** This file defines a function for saving a copy of the original source data
	- for IRTS this is what has been harvested from other systems, before the individual values are saved in the metadata table.
	- for DOI Minter this is the generated metadata record sent to DataCite.

	** Parameters :
		$database : database connection (irts, doiMinter, etc.).
		$source : the name of the source system.
		$idInSource : the id of this record in the source system.
		$sourceData : the full record in the source format.
		$format : the format of the record (XML, JSON, etc.).
		
	** Output :	returns recordType (new, modified, or unchanged) and report.

	*/
	
	function saveSourceData($database, $source, $idInSource, $sourceData, $format)
	{
		global $errors;

		$report = '';

		//check for existing entry
		$check = select($database, "SELECT rowID, sourceData FROM sourceData WHERE source LIKE ? AND idInSource LIKE ? AND deleted IS NULL", array($source, $idInSource));

		//if not existing
		if(mysqli_num_rows($check) === 0)
		{
			$recordType = 'new';

			if(!insert($database, 'sourceData', array('source', 'idInSource', 'sourceData', 'format'), array($source, $idInSource, $sourceData, $format)))
			{
				$error = end($errors);
				$report .= ' - '.$error['type'].' error: '.$error['message'].PHP_EOL;
			}
		}
		else
		{
			$row = $check->fetch_assoc();
			$existingData = $row['sourceData'];
			$existingRowID = $row['rowID'];
			
			//if sourceData has changed, mark old sourceData as replaced
			if($existingData !== $sourceData)
			{
				$recordType = 'modified';

				if(!insert($database, 'sourceData', array('source', 'idInSource', 'sourceData', 'format'), array($source, $idInSource, $sourceData, $format)))
				{
					$error = end($errors);
					$report .= ' - '.$error['type'].' error: '.$error['message'].PHP_EOL;
				}
				$newRowID = $database->insert_id;

				if(!update($database, 'sourceData', array("deleted", "replacedByRowID"), array(date("Y-m-d H:i:s"), $newRowID, $existingRowID), 'rowID'))
				{
					$error = end($errors);
					$report .= ' - '.$error['type'].' error: '.$error['message'].PHP_EOL;
				}
			}
			else
			{
				$recordType = 'unchanged';
			}
		}
		return array('recordType' => $recordType, 'report' => $report);
	}
