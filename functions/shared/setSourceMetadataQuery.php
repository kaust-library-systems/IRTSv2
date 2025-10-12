<?php
	//Define function to set a query statement used for querying by or for values in the metadata table
	function setSourceMetadataQuery($source, $idInSource, $parentRow, $field, $value = NULL)
	{
		global $irts;

		if(!is_null($value))
		{
			$value = $irts->real_escape_string($value);

			if(is_string($field)&&!is_null($idInSource))
			{
				$query = "SELECT `rowID`, `idInSource`, parentRowID, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `field` = '$field' AND value = '$value' AND `deleted` IS NULL ORDER BY `place` ASC";
			}
			elseif(is_string($field))
			{
				$query = "SELECT `rowID`, `idInSource`, parentRowID, place FROM `metadata` WHERE `source` = '$source' AND `field` = '$field' AND value = '$value' AND `deleted` IS NULL ORDER BY `place` ASC";
			}
			elseif(is_array($field))
			{
				$fieldNames = array();
				foreach($field as $fieldName)
				{
					$fieldNames[] = "`field` = '$fieldName'";
				}
				$fieldNames = implode(' OR ', $fieldNames);

				$query = "SELECT `rowID`, `idInSource`, parentRowID, place FROM `metadata` WHERE `source` = '$source' AND ($fieldNames) AND value = '$value' AND `deleted` IS NULL ORDER BY `place` ASC";

				//echo $query.'<br>';
			}
		}
		elseif(is_null($parentRow))
		{
			if(is_string($field))
			{
				$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` IS NULL AND `field` = '$field' AND `deleted` IS NULL ORDER BY `place` ASC";
			}
			elseif(is_array($field))
			{
				$fieldNames = array();
				foreach($field as $fieldName)
				{
					$fieldNames[] = "`field` = '$fieldName'";
				}
				$fieldNames = implode(' OR ', $fieldNames);

				$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` IS NULL AND ($fieldNames) AND `deleted` IS NULL ORDER BY `place` ASC";

				//echo $query.'<br>';
			}
		}
		elseif($parentRow===TRUE)
		{			
			$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` IS NOT NULL AND `field` = '$field' AND `deleted` IS NULL ORDER BY `place` ASC";			
		}
		elseif(is_array($parentRow))
		{
			if(isset($parentRow['parentField']))
			{			
				$parentField = $parentRow['parentField'];

				$parentValue = $irts->real_escape_string($parentRow['parentValue']);

				$query = "SELECT child.`rowID`, child.value, child.place FROM `metadata` parent LEFT JOIN metadata child ON parent.rowID = child.parentRowID WHERE parent.`source` = '$source' AND parent.`idInSource` = '$idInSource' AND parent.field = '$parentField' AND parent.value = '$parentValue' AND parent.deleted IS NULL AND child.field ='$field' AND child.deleted IS NULL ORDER BY `place` ASC";
			}
			else
			{
				$parentRows = implode("','", $parentRow);
				
				$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` IN ('$parentRows') AND `field` = '$field' AND `deleted` IS NULL ORDER BY `place` ASC";
			}
		}
		elseif(is_array($field))
		{
			$fields = implode("','", $field);

			$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` = '$parentRow' AND `field` IN ('$fields') AND `deleted` IS NULL ORDER BY `place` ASC";
		}
		else
		{
			$query = "SELECT `rowID`, `value`, place FROM `metadata` WHERE `source` = '$source' AND `idInSource` = '$idInSource' AND `parentRowID` = '$parentRow' AND `field` = '$field' AND `deleted` IS NULL ORDER BY `place` ASC";
		}

		return $query;
	}
