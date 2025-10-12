<?php
	//This function makes all requests needed to create a new version of an item in DSpace, submit it to workflow, approve it for archiving, and update the new version metadata
    function dspaceVersionArchiveAndUpdateItem($itemUUID, $reasonForVersioning, $record, $owningCollectionID)
	{
        $responses = [];

        $status = 'success';

        //Create version
        $response = dspaceCreateVersion($itemUUID, $reasonForVersioning);

        $responses[] = $response;

        if($response['status'] != 'success')
        {
            $status = 'failed';
        }
        else
        {
            $body = json_decode($response['body'], TRUE);
            
            $versionID = $body['id'];

            //Get version item
            $response = dspaceGetVersionItem($versionID);

            $responses[] = $response;

            if($response['status'] != 'success')
            {
                $status = 'failed';
            }
            else
            {
                $body = json_decode($response['body'], TRUE);

                $versionItemUUID = $body['uuid'];

                //Get workspace item of version
                $response = dspaceGetWorkspaceItemByItemUUID($versionItemUUID);

                $responses[] = $response;

                if($response['status'] != 'success')
                {
                    $status = 'failed';
                }
                else
                {
                    $body = json_decode($response['body'], TRUE);

                    $workspaceItemID = $body['id'];

                    if(isset($body['errors']))
                    {
                        foreach($body['errors'] as $error)
                        {
                            if($error['message'] == 'error.validation.license.notgranted')
                            {
                                //Grant license
                                $response = dspaceGrantLicense($workspaceItemID);

                                $responses[] = $response;
                            }
                            elseif($error['message'] == 'error.validation.required')
                            {
                                foreach($error['paths'] as $path)
                                {
                                    $pathParts = explode('/', $path);

                                    $field = array_pop($pathParts);
                                    
                                    if($field == 'pubs.publication-status')
                                    {
                                        //Set publication status
                                        $response = dspaceSetPublicationStatus($workspaceItemID, $path);

                                        $responses[] = $response;
                                    }
                                }
                            }
                        }
                    }
                    
                    //Submit workspace item of version to workflow
                    $response = dspaceSubmitWorkspaceItemToWorkflow($workspaceItemID);

                    $responses[] = $response;

                    if($response['status'] != 'success')
                    {
                        $status = 'failed';
                    }
                    else
                    {
                        $body = json_decode($response['body'], TRUE);

                        $workflowItemID = $body['id'];

                        //Get pool task
                        $response = dspaceGetPoolTaskByItemUUID($versionItemUUID);

                        $responses[] = $response;

                        if($response['status'] != 'success')
                        {
                            $status = 'failed';
                        }
                        else
                        {
                            $body = json_decode($response['body'], TRUE);

                            $poolTaskID = $body['id'];

                            //Claim pool task
                            $response = dspaceClaimPoolTask($poolTaskID);

                            $responses[] = $response;

                            if($response['status'] != 'success')
                            {
                                $status = 'failed';
                            }
                            else
                            {
                                $body = json_decode($response['body'], TRUE);

                                $claimedTaskID = $body['id'];
                                
                                //Approve pool task
                                $response = dspaceApproveClaimedTask($claimedTaskID);

                                $responses[] = $response;

                                if($response['status'] != 'success')
                                {
                                    $status = 'failed';
                                }
                                else
                                {
                                    //Get item
                                    $response = dspaceGetItem($versionItemUUID);

                                    $responses[] = $response;

                                    if($response['status'] != 'success')
                                    {
                                        $status = 'failed';
                                    }
                                    else
                                    {
                                        $body = json_decode($response['body'], TRUE);

                                        $versionMetadata = $body['metadata'];
                                    
                                        $metadataToKeep = array();
                                        $metadataToKeep['dc.date.accessioned'] = $versionMetadata['dc.date.accessioned'];
                                        $metadataToKeep['dc.description.provenance'] = $versionMetadata['dc.description.provenance'];
                                        
                                        $itemJSON = dspacePrepareItem($record, $versionItemUUID, $metadataToKeep);
                                        
                                        //Update item metadata
                                        $response = dspaceUpdateItem($versionItemUUID, $itemJSON);

                                        $responses[] = $response;

                                        if($response['status'] != 'success')
                                        {
                                            $status = 'failed';
                                        }
                                        else
                                        {
                                            $response = dspaceGetOwningCollection($versionItemUUID);

                                            $responses[] = $response;

                                            if($response['status'] != 'success')
                                            {
                                                $status = 'failed';
                                            }
                                            else
                                            {
                                                $body = json_decode($response['body'], TRUE);

                                                $currentOwningCollectionID = $body['id'];

                                                if($currentOwningCollectionID != $owningCollectionID)
                                                {
                                                    //Move item to new owning collection
                                                    $response = dspaceMoveItem($versionItemUUID, $owningCollectionID);

                                                    $responses[] = $response;

                                                    if($response['status'] != 'success')
                                                    {
                                                        $status = 'failed';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
       
		return array('status' => $status, 'responses' => $responses, 'newVersionUUID' => $versionItemUUID);
	}