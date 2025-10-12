<?php
	//Define function to update collection records
	function updateRepositoryCollections($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{
			$page = 0;

			//continue paging until no further results are returned
			$continuePaging = TRUE;

			while($continuePaging)
			{
				$response = dspaceListRecords('collections', $page);

				if($response['status'] == 'success')
				{
					$results = json_decode($response['body'], TRUE);

					$totalPages = $results['page']['totalPages'];

					echo $totalPages.PHP_EOL;
					
					foreach($results['_embedded']['collections'] as $collection)
					{
						$uuid = $collection['uuid'];

						echo $uuid.PHP_EOL;
						
						//$result = setDisplayFields($metadata);

						$uri = $collection['metadata']['dc.identifier.uri'][0]['value'];

						echo $uri.PHP_EOL;

						if(strpos($uri, REPOSITORY_URL) !== FALSE)
						{
							//patch dc.identifier.uri field
							$patch[] = array("op" => "replace",
							"path" => "/metadata/dc.identifier.uri",
							"value" => array(array("value" => str_replace(REPOSITORY_URL.'/handle/','http://hdl.handle.net/', $uri))));
							
							$patchJSON = json_encode($patch);
							
							$response = dspacePatchMetadata('collections', $uuid, $patchJSON);
							
							echo $response['status'].PHP_EOL;

							if($response['status'] === 'failed')
							{
								print_r($response);
							}
						}

						set_time_limit(0);
						ob_flush();

						//sleep(5);
					}

					$page++;

					if($page >= $totalPages)
					{
						$continuePaging = FALSE;
					}
				}
				else
				{
					print_r($response);
					
					//sleep(5);

					//$continuePaging = FALSE;
				}

				//break after 1st query
				//$continuePaging = FALSE;
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
