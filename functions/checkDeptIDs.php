<?php
	//Define function to return all relevant departmental ids for a given person
	function checkDeptIDs($localPersonID, $pubdate)
	{
		global $irts, $message;
		
		$thisAuthorDeptIds = getValues($irts, "SELECT target.`rowID`, target.value FROM `metadata` target LEFT JOIN metadata sibling USING(parentRowID)
		WHERE target.`source` = 'local'
		AND target.`idInSource` = '$localPersonID'
		AND target.field = 'local.org.id'
		AND target.deleted IS NULL
		AND sibling.field = 'local.date.start'
		AND sibling.value < '$pubdate'
		AND sibling.deleted IS NULL
		AND target.rowID NOT IN (
			SELECT target.`rowID` FROM `metadata` target LEFT JOIN metadata sibling USING(parentRowID) WHERE target.`source` = 'local'
			AND target.`idInSource` = '$localPersonID'
			AND target.field = 'local.org.id'
			AND target.deleted IS NULL
			AND sibling.field = 'local.date.end'
			AND sibling.value < '$pubdate'
			AND sibling.deleted IS NULL)", array('value'), 'arrayOfValues');

		if(empty($thisAuthorDeptIds))
		{
			$thisAuthorDeptIds = getValues($irts, "SELECT target.`rowID`, target.value FROM `metadata` target 
				WHERE target.`source` = 'local' 
				AND target.`idInSource` = '$localPersonID' 
				AND target.field = 'local.org.id' 
				AND target.deleted IS NULL", array('value'), 'arrayOfValues');

			//$message .= '<br> - Pub date may not fall within departmental affiliation range - any dept id added that we could find!!!';
		}

		//replace alternate org IDs with primary org id
		foreach($thisAuthorDeptIds as $key => $deptId)
		{
			$primaryID = getValues($irts, "SELECT idInSource FROM `metadata`  
				WHERE `source` = 'local' 
				AND field = 'local.org.alternateID'
				AND value = '$deptId' 				
				AND deleted IS NULL", array('idInSource'), 'singleValue');
				
			if(!empty($primaryID))
			{
				$thisAuthorDeptIds[$key] = str_replace('org_', '', $primaryID);
			}
		}

		$thisAuthorDeptIds = array_unique($thisAuthorDeptIds);
		$thisAuthorDeptIds = array_filter($thisAuthorDeptIds);

		$facultyID = getValues($irts, "SELECT DISTINCT idInSource FROM `metadata`
				WHERE `source` LIKE 'local'
				AND `idInSource` = '$localPersonID'
				AND `field` LIKE 'local.employment.type'
				AND value LIKE 'Faculty'
				AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		
		//Skip check for parent org units for faculty because they will have a direct affiliation to a division
		if(empty($facultyID))
		{
			//check for divisions if only program or research center is given
			$thisAuthorDeptIds = checkParentOrgUnit($thisAuthorDeptIds);
		}

		return $thisAuthorDeptIds;
	}
