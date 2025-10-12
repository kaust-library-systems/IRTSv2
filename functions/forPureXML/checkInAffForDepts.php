<?php
	//Define function to check for dept names in the full affs
	function checkInAffForDepts($fullAffs, $kaustDeptsRows, &$orgunitnumbers, &$ownerid)
	{
		global $message;
		
		//Check for Org Unit Numbers
		foreach($kaustDeptsRows as $kaustDeptRow)
		{
			$orgunit = $kaustDeptRow['orgUnitNumber'];
			$parentorgunit = $kaustDeptRow['parentOrgUnitNumber'];
			$orgname = $kaustDeptRow['ControlledName'];
			$namestrings = explode('||', $kaustDeptRow['StringsToMatch']);
			$orgtype = $kaustDeptRow['Type'];
												
			array_push($namestrings, $orgname);
		
			foreach($namestrings as $string)
			{
				if(!empty($string))
				{
					if(strpos($fullAffs, $string)!==FALSE)
					{
						if(!empty($orgunit))
						{
							if(!in_array($orgunit, $orgunitnumbers))
							{
								array_push($orgunitnumbers, $orgunit);
							}
							if(empty($ownerid))
							{
								if($orgtype == 'Division' || $orgtype == 'Core Lab')
								{
									$ownerid = $orgunit;
								}
							}
						}
						elseif(!empty($parentorgunit))
						{
							if(!in_array($parentorgunit, $orgunitnumbers))
							{
								array_push($orgunitnumbers, $parentorgunit);
							}
							if(empty($ownerid))
							{
								if($parentorgunit == '30000283' || $parentorgunit == '30000284' || $parentorgunit == '30000171')
								{
									$ownerid = $parentorgunit;
								}
							}
						}
						else
						{
							$message .= '<br> - Unit identified without org id or parent id: '.$orgname.' in the full aff: '.$fullAffs;
						}
						break 1;
					}
				}
			}
		}
	}	
