<?php
	function dspaceCheckProvenanceAndReauthenticate($results, $query, $checkCount)
	{
		//default status
		$status = 'failed';
		
		if($checkCount < 5)
		{
			//flag to check if there were any "item" type records in the response
			$itemFound = FALSE;
			
			//flag to check if a provenance field was found in the metadata
			$provenanceFound = FALSE;

			foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
			{
				if(isset($result['_embedded']['indexableObject']))
				{				
					$object = $result['_embedded']['indexableObject'];

					if($object['type'] === 'item')
					{
						$itemFound = TRUE;
						
						//if at least one item record in the response has a hidden field (provenance), reauthentication is not needed
						if(isset($object['metadata']['dc.description.provenance']))
						{
							$provenanceFound = TRUE;

							$status = 'success';

							break;
						}
					}
				}
			}

			//if bearer token has expired, metadata will not have hidden fields, reauthentication is needed. If no item records were found, reauthentication is not needed.
			if($itemFound && !$provenanceFound)
			{
				//sleep longer each time
				sleep($checkCount * 10);
							
				//Get initial CSRF token and set in session
				$response = dspaceGetStatus();
													
				//Log in
				$response = dspaceLogin();

				$response = dspaceSearch($query);

				if($response['status'] == 'success')
				{
					$results = json_decode($response['body'], TRUE);

					$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

					echo $totalPages.PHP_EOL;

					//recursively call function to check if reauthentication succeeded
					$result = dspaceCheckProvenanceAndReauthenticate($results, $query, $checkCount + 1);

					$results = $result['results'];
					$status = $result['status'];
				}
			}
		}

		return ['results' => $results, 'status' => $status];
	}