<?php
	//This function makes all requests needed to prepare and apply a patch to a set of policies for an item or bitstream in DSpace to update the embargo
    function dspacePrepareAndApplyPatchesToPolicies($resourceID, $newEmbargoEndDate)
	{
        //if any request in the process fails, this will be set to failed
        $status = 'success';
        
        $report = 'Attempt to update resource policies for resource: '.$resourceID.PHP_EOL;

        $report .= '- New embargo end date: '.$newEmbargoEndDate.PHP_EOL;

        $errors = [];
        
        $response = dspaceGetResourcePolicies('resource', $resourceID);

        if($response['status'] == 'success')
        {
            $policies = json_decode($response['body'], TRUE);

            if(isset($policies['_embedded']['resourcepolicies']))
            {
                foreach ($policies['_embedded']['resourcepolicies'] as $policy)
                {
                    //only update policies for anonymous group
                    if(!empty($policy['_embedded']['group']) && $policy['_embedded']['group']['name'] == 'Anonymous')
                    {
                        $policyPatch = [];
                        
                        $policyID = $policy['id'];

                        $report .= '-- Policy ID: '.$policyID.PHP_EOL;
                        
                        //If start date is set, replace it, otherwise add it
                        if(!empty($policy['startDate']))
                        {
                            $policyPatch[] =  array("op" => "replace",
                                    "path"=> "/startDate",
                                    "value" => $newEmbargoEndDate);
                        }
                        else
                        {
                            $policyPatch[] =  array("op" => "add",
                                    "path"=> "/startDate",
                                    "value" => $newEmbargoEndDate);
                        }

                        $policyPatchJSON = json_encode($policyPatch);

                        //apply the patch
                        $response = dspaceUpdateResourcePolicy($policyID, $policyPatchJSON);

                        if($response['status'] == 'success')
                        {
                            $report .= '--- Successfully updated embargo until: '.$newEmbargoEndDate.PHP_EOL;
                        }
                        else
                        {
                            $report .= '--- failed to patch resource policy'.PHP_EOL;
                            
                            $report .= print_r($response, TRUE);

                            $errors[] = $report;

                            $status = 'failed';
                        }
                    }
                }
            }
        }
        else
        {
            $report .= '- Failed to retrieve resource policies: '.PHP_EOL;
            
            $report .= print_r($response, TRUE);

            $errors[] = $report;

            $status = 'failed';
        }
       
		return array('status' => $status, 'report' => $report, 'errors' => $errors);
	}