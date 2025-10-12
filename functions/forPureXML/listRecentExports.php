<?php

/*

**** This file defines a function to show a table that lists the recent Pure XML exports that are available for retrieval.

** Parameters :
	No parameters required

** Created by : Yasmeen Alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 15 December 2019- 5:00 PM

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function listRecentExports($tab){

	// database
	global $irts;

	// set a variable
	$table = '';

	if($tab === 'individualExportForm')
	{
		$fullName  = '';

		//last 10 scopus authorID exports for Pure
		$lastTen = getValues($irts, "SELECT `idInSource`, `sourceData`, `added` FROM `sourceData` 
			WHERE `source` = 'forPure_scopusExport' 
			AND `format` = 'JSON' 
			AND	`idInSource` like 'authorID%'
			AND `deleted` IS NULL 
			ORDER BY `added` DESC 
			LIMIT 10", array('idInSource', 'sourceData' , 'added'), 'arrayOfValues');

		// init the table in html
		$table .= '<br>
		<br>
		<p style="display: inline"><u><b>Recently Generated: </b></u></p>';

		// add the bulk button
		$table .= '
		<form action="pureExport.php" method="post" name="retrieveXML" class="float-right">
		<input type="hidden" name="exportID" value="facultyAuthorIDs_All">
		<button type="submit" id="submit" class="btn btn-info" >Bulk export for all faculty</button>
		</form>';

		$table .= '
		<table style="width:100%">
		<tr>
			<th>Scopus ID</th>
			<th>Name</th>
			<th>Number of items</th>
			<th>Pure XML</th>
			<th>Generated date</th>

		</tr>';

		foreach ($lastTen as $author)
		{
			$idInSource = $author['idInSource'];
			
			//split the id to get the person scopus ID
			$scopusAuthorID = explode('_', $idInSource)[1];

			//count the number of items
			$itemCount = count(json_decode($author['sourceData'], true));

			//get the name from scopus
			$authorInfoFromScopus = getValues($irts, "SELECT `sourceData` FROM `sourceData` 
				WHERE `source` = 'scopus' 
				AND	`idInSource` = '$idInSource'
				AND `format` = 'JSON' 
				AND `deleted` IS NULL", array('sourceData'), 'singleValue');
			
			if(empty($authorInfoFromScopus))
			{
				$authorInfoFromScopus = getAuthorInfoFromScopus($scopusAuthorID);
				
				if(is_string($authorInfoFromScopus))
				{
					$recordType = saveSourceData($report, 'scopus', $idInSource, $authorInfoFromScopus, 'JSON');
				}
			}
			
			//print_r($authorInfoFromScopus);

			if(is_string($authorInfoFromScopus))
			{
				//get the author name
				$authorInfoFromScopus = json_decode($authorInfoFromScopus, TRUE);

				$surname = $authorInfoFromScopus['author-retrieval-response'][0]['author-profile']['preferred-name']['surname'];
				$firstName = $authorInfoFromScopus['author-retrieval-response'][0]['author-profile']['preferred-name']['given-name'];

				$fullName = $surname.', '.$firstName;
			}
			else
			{
				$fullName  = '';
			}

			// create the table/ add button to download the pure file from the database
			$table .=  '<tr>
			<td>'.$scopusAuthorID.'</td>
			<td>'.$fullName.'</td>
			<td>'.$itemCount.'</td>
			<td>
			<form action="pureExport.php" method="post" name="retrieveXML" >
			<input type="hidden" name="exportID" value="'.$author['idInSource'].'">
			<button type="submit" id="submit" class="btn btn-info" >Retrieve Pure XML</button>
			</div>
			</form>
			</td>
			<td>'.$author['added'].'</td>
			</tr>';
		}

		$table .= '</table>';
	}
	elseif($tab === 'repositoryExportForm')
	{
		//most recent repository itemType exports
		$todaysExports =  getValues($irts, "SELECT `idInSource`, `sourceData`, `added` FROM `sourceData` 
			WHERE `source` = 'forPure_repositoryExport' 
			AND `format` = 'JSON' 
			AND `idInSource` LIKE 'dc.type%' 
			AND `deleted` IS NULL 
			AND `added` LIKE '".TODAY."%'", array('idInSource', 'sourceData', 'added'), 'arrayOfValues');

		// init the table in html
		$table .= '<br>
		<br>
		<p><u><b>Exports for '.TODAY.': </b></u></p><table style="width:100%"> <tr>
			<th>Item Type</th>
			<th>Export Type</th>
			<th>Number of items</th>
			<th>Pure XML</th>
			<th>Generated date</th>

		</tr>';

		foreach ($todaysExports as $exportID) {

			$itemType = explode('_', $exportID['idInSource'])[1];

			$exportType = explode('_', $exportID['idInSource'])[2];

			//count the number of items
			$itemCount = count(json_decode($exportID['sourceData'], true));

			// create the table/ add button to download the pure file from the database
			$table .=  '<tr>
			<td>'.$itemType.'</td>
			<td>'.$exportType.'</td>
			<td>'.$itemCount.'</td>
			<td>
			<form action="pureExport.php" method="post" name="retrieveXML" >
			<input type="hidden" name="exportID" value="'.$exportID['idInSource'].'">
			<button type="submit" id="submit" class="btn btn-info" >Retrieve Pure XML</button>
			</form>
			</td>
			<td>'.$exportID['added'].'</td>
			</tr>';
		}

		$table .= '</table>';
	}

	// return the file to the table to interface
	return $table;
}
