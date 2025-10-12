<?php
	$message .= 'Item UUID: '.$itemUUID.'<br>';

	$response = dspaceGetItem($itemUUID);

	if($response['status'] == 'success')
	{
		$itemJSON = $response['body'];
		
		$item = json_decode($itemJSON, TRUE);

		$handle = $item['handle'];

		$message .= 'Item Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'">'.$handle.'</a><br>';

		//save the sourceData in the database
		$result = saveSourceData($irts, 'dspace', $itemUUID, $itemJSON, 'JSON');

		$recordType = $result['recordType'];
		
		//process the record
		$result = processDspaceRecord($itemJSON);

		$record = $result['record'];

		$message .= $result['report'];
		
		//save it in the database
		$result = saveValues('dspace', $itemUUID, $record, NULL);

		$result = saveValues('repository', $handle, $record, NULL);

		$metadata = $item['metadata'];

		//check availability selection for embargo
		if(strpos($metadata['kaust.availability.selection'][0]['value'], "Embargo") !== FALSE)
		{
			$embargoRequested = TRUE;
		}
		else
		{
			$message .= ' - Embargo not requested: '.$metadata['kaust.availability.selection'][0]['value'].'<br>'.PHP_EOL;
			
			$embargoRequested = FALSE;
		}

		//add embargo information to metadata
		if($embargoRequested)
		{
			$embargoEndDate = ONE_YEAR_LATER;

			$metadata['dc.rights.embargodate'][0]['value'] = $embargoEndDate;

			$message .= '- Embargo End Date added to metadata: '.$embargoEndDate.'<br>'.PHP_EOL;
		}
	
		$message .= '<h2>Thesis/Dissertation Metadata To Edit</h2>';

		// display metadata fields and values in table
		$message .= '<form action="reviewCenter.php?formType=checkThesisSubmission" method="post">';

		$message .= '<input type="hidden" name="page" value="'.$page.'">'; //used to support skipping to next submission
		$message .= '<input type="hidden" name="itemUUID" value="'.$itemUUID.'">';
		$message .= '<input type="hidden" name="handle" value="'.$handle.'">';
		$message .= '<table class="metadata-table" style="border-collapse: collapse; border: 1px solid black;">
								<tr>
									<th style="border: 1px solid black;">Field</th>
									<th style="border: 1px solid black; width: 100%">Values</th>
								</tr>';

		foreach($metadata as $field => $values)
		{
			$label = isset($template[$field]['label']) ? $template[$field]['label'] : $field;

			$message .= '<tr>
							<td style="border: 1px solid black;">'.$label.'</td>
							<td>
							<table>
								<tr>
								<th style="border: 1px solid black;">Value</th>
								<th style="border: 1px solid black;">Delete Row Button</th>
								<th style="border: 1px solid black;">Add Row Button</th>
							</tr>';

			foreach($values as $key => $value)
			{
				$textareaRows = (int)round(strlen($value['value'])/50);

				if($textareaRows===0)
				{
					$textareaRows = 1;
				}
				
				$message .= '<tr id="'.$field.'_'.$key.'">
									<td style="border: 1px solid black; width: 100%"><textarea rows="'.$textareaRows.'" name="metadata['.$field.']['.$key.'][value]" style="width: 100%">'.$value['value'].'</textarea></td>
									<td style="border: 1px solid black;">
										<button type="button" class="btn btn-danger remove-row" id="remove_'.$field.'_'.$key.'">Remove Row</button>
									</td>
									<td colspan="2" style="border: 1px solid black;">
										<button type="button" class="btn btn-success add-row" id="add_'.$field.'_'.$key.'">Add Row</button>
									</td>
								</tr>';
			}

			$message .= '</table></td></tr>';
		}

		$message .= '</table>';

		$message .= '<br><button class="btn btn-block btn-success" type="submit" name="action" value="updateMetadata">-- Update Item Metadata --</button>';
		$message .= '</form>';

	}
	else
	{
		$message .= '<div class="col-sm-12 alert-warning border border-dark rounded">Error Retrieving Item: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></div>';
	}
?>