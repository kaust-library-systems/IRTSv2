<?php
	//Define function to add owning organizational unit to Pure XML
	function owner($deptIDs)
	{
		global $irts;

		if(!empty($deptIDs))
		{
			$deptIDs = array_unique($deptIDs);
			$deptIDs = array_filter($deptIDs);
			foreach($deptIDs as $deptID)
			{
				//Check for owning unit from identified deptIDs
				$orgtype = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, "local.org.type"), array('value'), 'singleValue');
				$parentorgunit = getValues($irts, setSourceMetadataQuery('local', 'org_'.$deptID, NULL, "local.org.parent"), array('value'), 'singleValue');

				if($orgtype === 'division' || $orgtype === 'corelab')
				{
					$ownerID = $deptID;
				}
				elseif(!empty($parentorgunit))
				{
					if($parentorgunit == '30000283' || $parentorgunit == '30000284' || $parentorgunit == '30000171')
					{
						$ownerID = $parentorgunit;
					}
				}

				if(!empty($ownerID))
				{
					break;
				}
			}
		}

		if(empty($ownerID))
		{
			//If no identified owning unit, the library will be the owner
			$ownerID = '30000068';
		}
		return $ownerID;
	}
