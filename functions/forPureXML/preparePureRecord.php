<?php
//Define function to process an article record
function preparePureRecord($input, $purexml, $authorID, $ownerID)
{
	global $irts, $report, $localPersonsScopusIDs;

	if(isset($input['dc.type'][0]['value']))
	{
		if(in_array($input['dc.type'][0]['value'], array('Thesis', 'Dissertation')))
		{
			$result = processTDRecord($input, $purexml);
		}
		elseif(in_array($input['dc.type'][0]['value'], array('Bioproject','Dataset')))
		{
			$result = processDatasetRecord($input, $purexml);
		}
		else
		{
			$result = processPublicationRecord($input, $purexml, $authorID, $ownerID);
		}
	}
	else
	{
		$result = processPublicationRecord($input, $purexml, $authorID, $ownerID);
	}

	// ob_flush();
	// set_time_limit(0);
	return $result;
}
