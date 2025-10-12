<?php
	//Define function to delete a set of bundles, this also deletes the bitstreams they contain
	function deleteBundles($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		//Get initial CSRF token and set in session
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();

		if($response['status'] == 'success')
		{
			/* $query = "SELECT DISTINCT idInSource FROM metadata 
                WHERE `source` LIKE 'dspace' 
                AND `field` LIKE 'dspace.handle' 
                AND `deleted` IS NULL 
                AND value IN (
                    SELECT idInSource FROM metadata 
                    WHERE `source` LIKE 'repository' 
                    AND `field` LIKE 'dspace.bundle.name' 
                    AND value LIKE 'tiles_%' 
                    AND `deleted` IS NULL
                )
                ORDER BY idInSource ASC";
            
            $itemUUIDs = getValues($irts, $query, array('idInSource')); 
            
            foreach ($itemUUIDs as $itemUUID)
            {
                echo 'Item UUID '.$itemUUID.PHP_EOL;

                $response = dspaceListItemBundles($itemUUID);
            */
            
            $baseQuery = 'f.itemtype=Image,equals&scope=1f539587-cec3-4b7c-bf8d-77612af68c7a';

            $page = 0;

			//continue paging until no further results are returned
			$continuePaging = TRUE;

			while($continuePaging)
			{
				if(!empty($page))
				{
					$query = $baseQuery.'&page='.$page;
				}
				else
				{
					$query = $baseQuery;
				}

				echo $query.PHP_EOL;
				
				$response = dspaceSearch($query);

				if($response['status'] == 'success')
				{
					$results = json_decode($response['body'], TRUE);

					$totalPages = $results['_embedded']['searchResult']['page']['totalPages'];

					echo $totalPages.PHP_EOL;
					
					foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
					{
						$tilesBundleExists = FALSE;
                        
                        $tilesBundleDeleted = FALSE;
                        
                        $item = $result['_embedded']['indexableObject'];
						
						$itemUUID = $item['uuid'];

						echo $itemUUID.PHP_EOL;

                        $alreadyDeleted = getValues($irts, setSourceMetadataQuery('dspaceBundleRemoval', $itemUUID, NULL, 'tiles.bundle.deleted'), array('value'), 'singleValue');

                        if(empty($alreadyDeleted))
                        {
                            echo 'Item UUID '.$itemUUID.PHP_EOL;

                            sleep(20);

                            $response = dspaceListItemBundles($itemUUID);

                            if($response['status'] == 'success')
                            {
                                $bundles = json_decode($response['body'], TRUE);

                                foreach($bundles['_embedded']['bundles'] as $bundle)
                                {
                                    echo '- Bundle named '.$bundle['name'].PHP_EOL;
                                    echo '- Bundle UUID '.$bundle['uuid'].PHP_EOL;
                                    if(strpos($bundle['name'], 'tiles_') !== FALSE)
                                    {
                                        $tilesBundleExists = TRUE;
                                        
                                        sleep(20);
                                        
                                        $response = dspaceDeleteBundle($bundle['uuid']);

                                        if($response['status'] == 'success')
                                        {
                                            echo '-- Bundle named '.$bundle['name'].' deleted'.PHP_EOL;

                                            $tilesBundleDeleted = TRUE;
                                        }
                                        else
                                        {
                                            print_r($response);

                                            //if bearer token has expired, reauthentication is needed
                                            //Get initial CSRF token and set in session
                                            $response = dspaceGetStatus();
                                                                                
                                            //Log in
                                            $response = dspaceLogin();
                                        }
                                    }
                                }

                                if(!$tilesBundleExists || $tilesBundleDeleted)
                                {
                                    $result = saveValue('dspaceBundleRemoval', $itemUUID, 'tiles.bundle.deleted', 1, TODAY, NULL);
                                }
                            }
                            else
                            {
                                print_r($response);

                                //if bearer token has expired, reauthentication is needed
                                //Get initial CSRF token and set in session
                                $response = dspaceGetStatus();
                                                                    
                                //Log in
                                $response = dspaceLogin();
                            }
                        }
                        else
                        {
                            echo 'Item UUID '.$itemUUID.' already processed'.PHP_EOL;
                        }

                        ob_flush();
                        set_time_limit(0);
                        sleep(60);
                    }

                    if(!isset($results['_embedded']['searchResult']['_links']['next']))
					{
						$continuePaging = FALSE;
					}
					else
					{
						if($page >= $totalPages)
						{
							$continuePaging = FALSE;
						}

                        $page++;
					}
				}
                else
                {
                    print_r($response);

                    $continuePaging = FALSE;
                }
            }
		}
        else
        {
            print_r($response);
        }

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
