<?php
	//Define function to check if a record already exists in the repository with the given attribute
	function checkForExistingRecords($value, $type, &$report, $source = 'repository')
	{
		global $irts;
		
		$checkForExistingRecordsTime = microtime(true);

		$singleFields = array('dc.title','dc.identifier.arxivid','dc.identifier.doi','dc.identifier.uri');

		if(in_array($type, $singleFields))
		{
			$existingRecords = getValues($irts, setSourceMetadataQuery($source, NULL, NULL, $type, $value), array('idInSource'), 'arrayOfValues');
			
			if(!isset($_GET['ignoreVariantTitles']))
			{
				if($type === 'dc.title')
				{
					/* //query can not include colon
					$titleToSearch = str_replace(':','',$value);

					//spaces must be url encoded
					$titleToSearch = str_replace(' ','%20',$titleToSearch);
					
					$query = 'query='.$titleToSearch;
					
					$response = dspaceSearch($query);

					if($response['status'] == 'success')
					{
						$results = json_decode($response['body'], TRUE);

						foreach($results['_embedded']['searchResult']['_embedded']['objects'] as $result)
						{
							$item = $result['_embedded']['indexableObject'];

							if(isset($item['metadata']['dc.title']))
							{
								$titleToCompare = $item['metadata']['dc.title'][0]['value'];

								if(similar_text(strtolower($value), strtolower($titleToCompare), $percentSimilar) > 10)
								{
									if($percentSimilar > 90)
									{
										$existingRecords[] = $item['handle'];
									}
								}
							}
						}
					} */					
				}
			}
		}
		
		$checkForExistingRecordsTime = microtime(true)-$checkForExistingRecordsTime;

		insert($irts, 'messages', array('process', 'type', 'message'), array('checkForExistingRecordsTime', 'report', $checkForExistingRecordsTime.' seconds for '.$source.' '.$type.' '.$value));

		if(isset($existingRecords))
		{
			$existingRecords = array_unique($existingRecords);
			//$report .= ' - Existing Records: '.implode($existingRecords).PHP_EOL;
			return $existingRecords;
		}
		else
		{
			//$report .= '<br> - No Existing Records';
			return array();
		}
	}
