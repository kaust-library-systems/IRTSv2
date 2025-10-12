<?php
	//Define function to generate an id that does not yet have an entry for this source
	function generateNewID($source)
	{
		global $irts;
		
		$newID = substr(str_shuffle(str_repeat(str_repeat("0123456789", 3)."abcdefghijklmnopqrstuvwxyz", 5)), 0, 8);
					
		//check for existing
		$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
			WHERE `source` LIKE '$source' 
			AND `idInSource` LIKE '$newID' 
			AND `deleted` IS NULL", array('idInSource'), 'singleValue');
		
		//generate new id if already used			
		while(!empty($existing))
		{
			$idInIRTS = substr(str_shuffle(str_repeat(str_repeat("0123456789", 3)."abcdefghijklmnopqrstuvwxyz", 5)), 0, 8);
			
			//check for existing
			$existing = getValues($irts, "SELECT idInSource FROM `metadata` 
			WHERE `source` LIKE '$source' 
			AND `idInSource` LIKE '$newID' 
			AND `deleted` IS NULL", array('idInSource'), 'singleValue');	
		}
		
		return $newID;
	}
