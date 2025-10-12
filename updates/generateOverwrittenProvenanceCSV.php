<?php
	//Define function to list provenance entries that have been overwritten so they can be added back via CSV upload to DSpace
	function generateOverwrittenProvenanceCSV($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$itemsWithCollections = array();
		
		if(isset($_GET['handle']))
		{
			$handles = array($_GET['handle']);
		}
		else
		{
			$handles = getValues($irts, "SELECT idInSource, value, count(`place`) FROM metadata WHERE source='repository'
					AND field='dspace.collection.handle'
					AND deleted IS NULL
					GROUP BY `idInSource`, value HAVING count(`place`) > 1", array('idInSource'), 'arrayOfValues');
		}

		$handles = array_unique($handles);

		foreach($handles as $handle)
		{
			$recordTypeCounts['all']++;
			
			$itemID = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.internalItemId'), array('value'), 'singleValue');
			
			$collections = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dspace.collection.handle'), array('value'));
			
			$clean = array();

			$unique = array_unique($collections);

			$collectionsCount = array_count_values($collections);			
			
			foreach($collectionsCount as $collection => $count)
			{
				if($count === 1)
				{
					$clean[] = $collection;
				}
			}
			
			$report .= $itemID.','.implode('||',$clean).','.implode('||',$unique).PHP_EOL;
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
