<?php
//Define function to generate and send repo
function PublicationCountsByDep($report)
{
    global $irts, $repository;
	// Prepare CSV output instead of HTML table
		$csvRows = array();
		$header = array('Type of Organizational Unit', 'Department Name');
				
		$years = array();
		$year = 2020;
		while($year < 2025)
		{
			$years[] = $year;
			$header[] = $year;
			$year++;
		}
		$csvRows[] = $header;

		$collections = getValues($irts, "SELECT `value` FROM metadata
		            WHERE `source` LIKE 'dspace'
					AND `field` LIKE 'dspace.collection.handle'
					AND `deleted` IS NULL
					AND value IN (
						SELECT `value` FROM metadata
						WHERE `source` LIKE 'local'
						AND `field` LIKE 'dspace.collection.handle'
						AND `deleted` IS NULL
					)", array('value'), 'arrayOfValues');
		print_r($collections);		
		foreach($collections as $collection)
		{
		
					
			$orgType = getValues($irts, "SELECT `value` FROM metadata
					WHERE `source` LIKE 'local'
					AND `field` LIKE 'local.org.type'
					AND `deleted` IS NULL
					AND idInSource IN (
						SELECT `idInSource` FROM metadata
						WHERE `source` LIKE 'local'
						AND `field` LIKE 'dspace.collection.handle'
						AND `value` LIKE '$collection'
						AND `deleted` IS NULL
					)", array('value'), 'singleValue');
							
			$deptName = getValues($irts, "SELECT `value` FROM metadata
						WHERE `source` LIKE 'dspace'
						AND `field` LIKE 'dspace.collection.name'
						AND `deleted` IS NULL
						AND idInSource IN (
							SELECT `idInSource` FROM metadata
							WHERE `source` LIKE 'dspace'
							AND `field` LIKE 'dspace.collection.handle'
							AND `value` LIKE '$collection'
							AND `deleted` IS NULL
						)", array('value'), 'singleValue');
					
			$row = array($orgType, $deptName);
			foreach($years as $year)
			{
				$itemCount = getValues($irts, "SELECT COUNT(`idInSource`) itemCount FROM metadata
						WHERE `source` LIKE 'repository'
						AND `field` LIKE 'dc.type'
						AND `value` IN ('Article','Book','Book Chapter','Conference Paper')
						AND `deleted` IS NULL
						AND idInSource IN (
							SELECT `idInSource` FROM `metadata` 
							WHERE `source` LIKE 'repository' 
							AND `field` LIKE 'dspace.collection.handle' 
							AND value LIKE '$collection' 
							AND `deleted` IS NULL
						)
						AND idInSource IN (
							SELECT `idInSource` FROM `metadata` 
							WHERE `source` LIKE 'repository' 
							AND `field` LIKE 'dc.date.issued' 
							AND value LIKE '$year%' 
							AND `deleted` IS NULL
						)", array('itemCount'), 'singleValue');
						$row[] = $itemCount;
			}
            $csvRows[] = $row;
        }

        // Custom sort order for orgType
        $orgTypeOrder = [
            'division' => 1,
            'program' => 2,
            'researchcenter' => 3
        ];

        // Sort $csvRows by orgType (first column), then by Department Name (second column), skipping the header row
        $header = array_shift($csvRows);
        usort($csvRows, function($a, $b) use ($orgTypeOrder) 
        {
            $aOrder = $orgTypeOrder[$a[0]] ?? 99;
            $bOrder = $orgTypeOrder[$b[0]] ?? 99;
            if ($aOrder === $bOrder) {
                return strcmp($a[1], $b[1]);
            }
            return $aOrder - $bOrder;
        });
        array_unshift($csvRows, $header);

        // Write CSV directly to a file
        $fp = fopen('/data/www/irts/bin/export/PblicationsBYdep.csv', 'w');
        foreach ($csvRows as $csvRow) 
        {
            fputcsv($fp, $csvRow);
        }
        fclose($fp);
			
		
}