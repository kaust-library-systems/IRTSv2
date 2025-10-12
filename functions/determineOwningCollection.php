<?php
	/*

	**** Define function to determine the owning collection for a record

	** Parameters :
		$record: item metadata record

	** Return :
		$message : report message
		$owningCollectionID : the ID of the owning collection

	*/

	//--------------------------------------------------------------------------------------------
	function determineOwningCollection($record)
	{
		$message = '';
		$owningCollectionID = '';
		
		//wih KAUST acknowledgement but without KAUST affiliation, put in acknowledgement only collection
		if(empty($record['dc.contributor.department'])&&empty($record[LOCAL_PERSON_FIELD])&&institutionNameInString($record['dc.description.sponsorship'][0]))
		{
			$owningCollectionID = ACKNOWLEDGEMENT_ONLY_COLLECTION_ID;
		}
		else //put in type collection in Research community
		{
			$typeCollectionFound = FALSE;
			
			$response = dspaceListCommunityCollections(RESEARCH_COMMUNITY_UUID);

			if($response['status'] == 'success')
			{
				$results = json_decode($response['body'], TRUE);
			
				foreach($results['_embedded']['collections'] as $collection)
				{
					//$message .= PHP_EOL.$collection['name'].' - '.$collection['id'];
					
					if(substr($collection['name'], -1) == 's')
					{
						if(rtrim($collection['name'], 's') == $record['dc.type'][0])
						{
							$owningCollectionID = $collection['id'];

							$typeCollectionFound = TRUE;

							break;
						}
					}
					elseif(rtrim($collection['name']) == $record['dc.type'][0])
					{
						$owningCollectionID = $collection['id'];

						$typeCollectionFound = TRUE;

						break;
					}
				}
			}
			else
			{
				//output error details in details tag
				$message .= '<div class="alert alert-danger">Failed to retrieve list of collections, details below: 
					<details>
						<summary> - Failure Response: </summary>
						<pre> - '.print_r($response, TRUE).'</pre>
					</details>
				</div>';
			}

			if(!$typeCollectionFound)
			{
				$message .= PHP_EOL.'Type collection not found for:'.$record['dc.type'][0].'. Create collection for this type, or change type to match an existing type, then resubmit.<br>';
			}
		}

		return ['message'=>$message, 'owningCollectionID'=>$owningCollectionID];
	}