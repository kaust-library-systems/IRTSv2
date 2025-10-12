<?php
	//Define function to switch titles and abstracts to use HTML tags for subscripts, superscripts, and italics instead of Latex
	function switchToHTMLTags($report, $errors, $recordTypeCounts) {
		global $irts;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success') {			
			//Get all the handles for items that have $ in the title or abstract
			$recordsToCheck = getValues(
				$irts, 
				"SELECT DISTINCT `idInSource`, `field`, `value` FROM metadata 
					WHERE source = 'repository' 
					AND field IN ('dc.title') 
					AND value LIKE '%\$%'
					AND deleted IS NULL", 
				array('idInSource', 'field', 'value'),
				'arrayOfValues');

			foreach($recordsToCheck as $recordsToCheck) {

				$handle = $recordsToCheck['idInSource'];

				$field = $recordsToCheck['field'];

				$value = $recordsToCheck['value'];

				$originalValue = $value;

				$uuid = getValues(
					$irts, 
					"SELECT `value` FROM `metadata` 
						WHERE `source` = 'repository' 
						AND `idInSource` = '$handle' 
						AND `field` = 'dspace.uuid' 
						AND `deleted` IS NULL",
					array('value'),
					'singleValue');
				
				$recordTypeCounts['all']++;

				$report .= $handle.PHP_EOL;

				$report .= $field.PHP_EOL;

				$report .= '- Old Value: '.$value.PHP_EOL;

				$value = convertLatexToHTML($value);

				$report .= '- New Value: '.$value.PHP_EOL;

				//Patch the title in the repository
				if($value != $originalValue) {
					$patches = array(array('op' => 'replace', 'path' => "/metadata/dc.title/0/value", 'value' => $value));

					$patchesJSON = json_encode($patches);
							
					$response = dspacePatchMetadata('items', $uuid, $patchesJSON);

					if($response['status'] == 'success')
					{
						$recordTypeCounts['modified']++;

						$report .= ' - Success'.PHP_EOL;
					}
					else
					{
						$errors[] = $response;

						$report .= ' - Error'.PHP_EOL;
					}
				}
				else
				{
					$recordTypeCounts['unchanged']++;

					$report .= ' - Unchanged'.PHP_EOL;
				}
			}
		}

		echo $report;

		$summary = saveReport($irts,__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
