<?php
	//Define function to mark matched rows as deleted
	
	/*
	** Parameters :
		$rowIDs : list of rowIDs to mark as deleted in the metadata table
		$source : name of the source system.
		$idInSource : id of this record in the source system.
	*/
	
	function markMatchedRowsAsDeleted($rowIDs, $source, $idInSource)		
	{
		global $irts;
		
		//if matched
		if(count($rowIDs) > 0)
		{
			foreach($rowIDs as $rowID)
			{
				update($irts, 'metadata', array("deleted"), array(date("Y-m-d H:i:s"), $rowID), 'rowID');
				
				//Recursively mark any children of this row as deleted as well
				markExtraMetadataAsDeleted($source, $idInSource, $rowID, '', '', '');
			}
		}
	}