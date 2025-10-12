<?php
	//html to display
	$message = '<div class="container">';

	$itemID = '';
	
	//get 1 item at a time (page is used to skip to the next item in the list) - use anonymous search to only return public records
	$response = dspaceSearch('query=(dc.rights.uri:creativecommons.org%20OR%20dc.identifier.arxivid:*)%20AND%20(dc.type:Article%20OR%20dc.type:%22Book%20Chapter%22%20OR%20dc.type:%22Conference%20Paper%22%20OR%20dc.type:Preprint)%20AND%20NOT%20arxiv.status:withdrawn&f.has_content_in_original_bundle=false,equals&scope='.RESEARCH_COMMUNITY_UUID.'&size=1&page='.$page, TRUE);

	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
		{
			$item = $result['_embedded']['indexableObject'];

			$itemID = $item['id'];

			$handle = $item['handle'];
		}

		if(empty($itemID))
		{
			$message .= 'No publication records with a Creative Commons license or an arXiv ID currently lack files.';
		}
		else
		{
			//add repository links to message
			$message .= displayRepositoryLinks($itemID, $handle);
			
			//add item info to message
			$message .= displayItemInfo($item['metadata']);

			//add rights details to message
			$message .= displayItemInfo($item['metadata'], '', 'rights', FALSE, array('dc.date.issued'));

			//unset display HTML fields
			unset($item['metadata']['display.details.right']);
			unset($item['metadata']['display.details.left']);
			unset($item['metadata']['display.relations']);

			//instructions to check for license on the publisher page
			$message .= '<div class="alert alert-info">Please check that the license and version information is correct before uploading the file.</div>';

			//button to go to upload file form with selections as hidden input
			$message .= '<form method="post" action="reviewCenter.php?formType=uploadFile">
				<input type="hidden" name="itemID" value="'.$itemID.'">
				<input type="hidden" name="handle" value="'.$handle.'">
				<input type="hidden" name="itemJSON" value="'.htmlspecialchars(json_encode($item)).'">
				<input type="hidden" name="selections" value="'.$selections.'">
				<input type="hidden" name="action" value="uploadFile">
				<input class="btn btn-success" type="submit" name="uploadFile" value="Upload File"></input>
			</form>';

			//button to skip to the next item
			$message .= '<form method="post" action="reviewCenter.php?formType=checkMissingFiles">
				<input type="hidden" name="action" value="skip">
				<input class="btn btn-primary" type="submit" name="checkMissingFiles" value="Skip to Next Item"></input>
			</form>';
		}
	}
	else
	{
		$message .= 'Search Error: <details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details>';
	}

	$message .= '</div>';

	echo $message;
