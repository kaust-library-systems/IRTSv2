<?php
	//Define function to add KAUST dept affs to Pure XML
	function addKaustDeptAffs($orgunitnumbers, $thisAuthorDeptIds, $aff)
	{
		global $purexml, $message;
		
		//We will prioritize the addition of all departments for a given person when the publication date falls within the dates of their affiliation to that department
		if(!empty($thisAuthorDeptIds))
		{
			foreach($thisAuthorDeptIds as $thisAuthorDeptId)
			{
				$purexml .= '<v1:organisation id="'.$thisAuthorDeptId.'"></v1:organisation>';
			}
		}
		elseif(!empty($orgunitnumbers))
		{
			foreach($orgunitnumbers as $orgid)
			{
				if(!empty($orgid))
				{
					$purexml .= '<v1:organisation id="'.$orgid.'"></v1:organisation>';
				}
			}
			$message .= '<br> - No dept ids identified for internal authors, using internal orgs identified from the full aff!!!';
		}
		else
		{
			//Use the highest level KAUST Org ID
			$purexml.= '<v1:organisation id="30000085"></v1:organisation>';
			
			$message .= '<br> - KAUST mentioned in full aff, but no identified depts!!! - and no dept id for internal person!!!! - The aff is: '.$aff;
		}
	}	
