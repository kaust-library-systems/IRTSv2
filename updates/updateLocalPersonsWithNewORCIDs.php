<?php
	//Define function to add ORCIDs from IOI to the local person data
	function updateLocalPersonsWithNewORCIDs($report, $errors, $recordTypeCounts)
	{
		global $irts;
	
		$result = $irts->query("SELECT m.`idInSource`, o.email, o.orcid 
		FROM ".IRTS_DATABASE.".metadata m 
		LEFT JOIN ".IOI_DATABASE.".`orcids` o 
		ON o.email = m.value
		WHERE o.orcid != '' 
		AND m.source = 'local' 
		AND m.field = 'local.person.email' 
		AND m.idInSource 
		NOT IN (
			SELECT `idInSource` 
			FROM ".IRTS_DATABASE.".metadata 
			WHERE source = 'local' 
			AND field = 'dc.identifier.orcid')");
			
		if($result->num_rows !== 0)
		{
			while($row = $result->fetch_assoc())
			{	
				$recordTypeCounts['all']++;
				$recordTypeCounts['new']++;
				
				$field = 'dc.identifier.orcid';
				$email = $row['email'];
				$orcid = $row['orcid'];
				
				$rowID = mapTransformSave('local', $row['idInSource'], '', $field, '', 1, $orcid, NULL);
				
				$report .= '<br>'.$recordTypeCounts['new'].'. New ORCID: '.$orcid.' added for '.$email;				
			}
		}
		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
?>