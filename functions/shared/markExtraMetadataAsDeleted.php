<?php
	//Define function to mark existing entries with place greater than current count as deleted
	
	/*
	** Parameters :
		$source : name of the source system.
		$idInSource : id of this record in the source system.
		$field : standard field name in the format namespace.element.qualifier .
		$place : current count of values for this field on this item.
		$parentRowID : if row is the child of another row, this will be the parent row's rowID, otherwise it will be NULL.
		$currentFields : array of field names used on the current record
	*/
	
	function markExtraMetadataAsDeleted($source, $idInSource, $parentRowID, $field, $place, $currentFields)
	{			
		global $irts;
		
		if(!empty($parentRowID)&&empty($field)&&empty($place)&&empty($currentFields))
		{
			//get list of rowIDs to mark as deleted
			$rowIDs = getValues(
				$irts, 
				"SELECT rowID FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID LIKE '$parentRowID' AND deleted IS NULL",
				array('rowID')
			);

			//Mark all children of a deleted row as deleted
			markMatchedRowsAsDeleted($rowIDs, $source, $idInSource);
		}
		elseif(!empty($field)&&is_int($place))
		{
			//get list of rowIDs to mark as deleted
			if($parentRowID === NULL)
			{
				$rowIDs = getValues(
					$irts, 
					"SELECT rowID FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID IS NULL AND field LIKE '$field' AND place > '$place' AND deleted IS NULL",
					array('rowID')
				);
			}
			else
			{
				$rowIDs = getValues(
					$irts, 
					"SELECT rowID FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID LIKE '$parentRowID' AND field LIKE '$field' AND place > '$place' AND deleted IS NULL",
					array('rowID')
				);
			}
			
			//mark existing entries with place greater than current count as deleted
			markMatchedRowsAsDeleted($rowIDs, $source, $idInSource);
		}
		elseif(!empty($currentFields))
		{
			//Mark metadata fields previously but no longer used on the item as deleted
			if(is_null($parentRowID))
			{
				$previousFields = getValues($irts, "SELECT DISTINCT field FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID IS NULL AND deleted IS NULL", array('field'));
			}
			else
			{
				$previousFields = getValues($irts, "SELECT DISTINCT field FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID LIKE '$parentRowID' AND deleted IS NULL", array('field'));
			}
			
			foreach($previousFields as $previousField)
			{
				if(!in_array($previousField, $currentFields))
				{					
					if(is_null($parentRowID))
					{
						$rowIDs = getValues(
							$irts,
							"SELECT rowID FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID IS NULL AND field LIKE '$previousField' AND deleted IS NULL",
							array('rowID')
						);
					}
					else
					{
						$rowIDs = getValues(
							$irts,
							"SELECT rowID FROM metadata WHERE source LIKE '$source' AND idInSource LIKE '$idInSource' AND parentRowID LIKE '$parentRowID' AND field LIKE '$previousField' AND deleted IS NULL",
							array('rowID')
						);
					}
					
					markMatchedRowsAsDeleted($rowIDs, $source, $idInSource);
				}
			}		
		}
	}