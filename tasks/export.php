<?php
	//Define function to update language values on metadata to null
	function export($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$source = 'repository';
		
		$community = '10754/324602';
		
		$fields = array('Type'=>'dc.type', 'Title'=>'dc.title', 'Authors'=>'dc.contributor.author', 'Journal'=>'dc.identifier.journal', 'DOI'=>'dc.identifier.doi', 'Handle'=>'dc.identifier.uri', 'Publication Date'=>'dc.date.issued', 'File'=>'dspace.bitstream.url');

		$result = $irts->query("SELECT idInSource FROM `metadata` WHERE `source` LIKE '$source' AND `field` LIKE 'dspace.community.handle' AND `value` LIKE '$community' AND `deleted` IS NULL AND idInSource IN (
			SELECT idInSource FROM `metadata` WHERE `source` LIKE '$source' 
			AND `field` LIKE 'dc.date.issued' 
			AND (
				`value` LIKE '2017%' 
				OR `value` LIKE '2018%' 
				OR `value` LIKE '2019%') 
			AND `deleted` IS NULL)");

		$works = array();
		while($row = $result->fetch_assoc())
		{
			$idInSource = $row['idInSource'];
			
			foreach($fields as $label=>$field)
			{
				$works[$idInSource][$label] = implode('||',getValues($irts, setSourceMetadataQuery($source, $idInSource, NULL, $field), array('value'), 'arrayOfValues'));
			}
		}

		// create a file pointer connected to the output stream
		/* $output = fopen('php://output', 'w');
		
		// output the column headings
		fputcsv($output, array_keys($fields));	
		
		foreach($works as $work)
		{
			fputcsv($output, $work);
		}
		
		//$csv = file_get_contents($output);
				
		fclose($output);
		
		$irts->query("INSERT INTO `messages`(`process`, `type`, `message`, `timestamp`) VALUES ('exportRepositoryResearchCommunityFor2017-2019','export','$csv', '".date("Y-m-d H:i:s")."')"); */
	}
