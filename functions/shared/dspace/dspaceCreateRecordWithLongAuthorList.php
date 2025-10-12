<?php
	//This function makes all requests needed to handle creation of a record with a long author list by first creating a record without author and affiliation information and then patching the record with the author and affiliation information
    function dspaceCreateRecordWithLongAuthorList($record, $owningCollectionID)
	{
        $itemReport = '';

        $errors = [];
        
        $status = 'success';

        //record creation response body to return to transfer script
        $body = '';
        
        $authors = $record['dc.contributor.author'];

        unset($record['dc.contributor.author']);

        $orcidAuthors = $record['orcid.author'];

        unset($record['orcid.author']);

        $affiliations = $record['dc.contributor.institution'];

        unset($record['dc.contributor.institution']);

        $result = setDisplayFields($record);

        $record = $result['metadata'];

        $itemJSON = dspacePrepareItem($record);

        $response = dspaceCreateItem($owningCollectionID, $itemJSON);

        if($response['status'] == 'success')
        {
            $body = $response['body'];
            
            $item = json_decode($response['body'], TRUE);

            if(!empty($item['id']))
            {
                $itemID = $item['id'];
                $handle = $item['handle'];

                //echo ' - Success: Item created with ID: '.$itemID.' and handle: '.$handle.PHP_EOL;
                
                $itemReport .= ' - Success: Item created with ID: '.$itemID.' and handle: '.$handle.PHP_EOL;

                $authorMetadata = array('dc.contributor.author'=>[]);

                //prepare and apply patches of 500 authors at a time
                $authorChunks = array_chunk($authors, 500);

                foreach($authorChunks as $chunk)
                {
                    $authorMetadata = array('dc.contributor.author'=>array_merge($authorMetadata['dc.contributor.author'], $chunk));

                    $response = dspacePrepareAndApplyPatchToItem($handle, $authorMetadata, __FUNCTION__);

                    $itemReport .= $response['report'];

                    $itemReport .= '-- '.$response['status'].PHP_EOL;

                    $errors = array_merge($errors, $response['errors']);

                    sleep(10);
                }

                $orcidAuthorMetadata = array('orcid.author'=>[]);

                //prepare and apply patches of 500 ORCID authors at a time
                $orcidAuthorChunks = array_chunk($orcidAuthors, 500);

                foreach($orcidAuthorChunks as $chunk)
                {
                    $orcidAuthorMetadata = array('orcid.author'=>array_merge($orcidAuthorMetadata['orcid.author'], $chunk));

                    $response = dspacePrepareAndApplyPatchToItem($handle, $orcidAuthorMetadata, __FUNCTION__);

                    $itemReport .= $response['report'];

                    $itemReport .= '-- '.$response['status'].PHP_EOL;

                    $errors = array_merge($errors, $response['errors']);

                    sleep(10);
                }

                $affiliationMetadata = array('dc.contributor.institution'=>[]);

                //prepare and apply patches of 500 affiliations at a time
                $affiliationChunks = array_chunk($affiliations, 500);

                foreach($affiliationChunks as $chunk)
                {
                    $affiliationMetadata = array('dc.contributor.institution'=>array_merge($affiliationMetadata['dc.contributor.institution'], $chunk));

                    $response = dspacePrepareAndApplyPatchToItem($handle, $affiliationMetadata, __FUNCTION__);

                    $itemReport .= $response['report'];

                    $itemReport .= '-- '.$response['status'].PHP_EOL;

                    $errors = array_merge($errors, $response['errors']);

                    sleep(10);
                }

                //add authors back to record
                $record['dc.contributor.author'] = $authors;

                //regenerate display fields
                $result = setDisplayFields($record);

                $record = $result['metadata'];

                $displayMetadata['display.details.left'] = $record['display.details.left'];

                $response = dspacePrepareAndApplyPatchToItem($handle, $displayMetadata, __FUNCTION__);

                $itemReport .= $response['report'];

                $itemReport .= '-- '.$response['status'].PHP_EOL;

                $errors = array_merge($errors, $response['errors']);
            }
            else
            {
                $status = 'failed';
                
                //output error details in details tag
                $itemReport .= '<div class="alert alert-success">Record may have been created, search the repository manually to confirm and find the handle.</div>
                    <div class="alert alert-danger">No ID or handle received,  details below: 
                    <details>
                        <summary> - Failure Response: </summary>
                        <pre> - '.print_r($response, TRUE).'</pre>
                    </details>
                    <details>
                        <summary> - Posted JSON was: </summary>
                        <pre> - '.$itemJSON.'</pre>
                    </details>
                </div>';
            }
        }
        else
        {
            $status = 'failed';
            
            $itemReport .= '<div class="alert alert-danger">Failed to create item, details below: 
                <details>
                    <summary> - Failure Response: </summary>
                    <pre> - '.print_r($response, TRUE).'</pre>
                </details>
                <details>
                    <summary> - Posted JSON was: </summary>
                    <pre> - '.$itemJSON.'</pre>
                </details>
            </div>';
        }
       
		return array('status' => $status, 'body' => $body, 'report' => $itemReport, 'errors' => $errors);
	}