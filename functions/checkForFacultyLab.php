<?php
	//Define function to return all relevant departmental ids for a given person
	function checkForFacultyLab($localPersonID)
	{
		global $irts, $message;
		
		$facultyLabCollectionID = '';
		
		//Only faculty group names are included directly in local person records, other org relations are identified by local org id
		$facultyLabName = getValues(
			$irts, 
			"SELECT `value` FROM metadata 
				WHERE source = 'local'
				AND `idInSource` = '$localPersonID' 
				AND field = 'local.org.name' 
				AND deleted IS NULL", 
			array('value'), 
			'singleValue');

			//echo $facultyLabName;

		if(!empty($facultyLabName))
		{
			$facultyLabCollectionID = getValues(
				$irts, 
				"SELECT `idInSource` FROM `metadata` 
					WHERE `source` LIKE 'dspace' 
					AND field LIKE 'dspace.name'
					AND `value` LIKE '$facultyLabName' 
					AND `idInSource` IN (
						SELECT idInSource FROM metadata WHERE field LIKE 'dspace.type' AND value LIKE 'collection'
					)
					AND `deleted` IS NULL", 
				array('idInSource'), 
				'singleValue');

			//echo $facultyLabCollectionID;
		}

		return array('name' => $facultyLabName, 'collectionID' => $facultyLabCollectionID);
	}
