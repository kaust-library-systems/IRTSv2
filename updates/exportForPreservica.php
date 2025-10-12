  <?php
function exportForPreservica($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$token = loginToDSpaceRESTAPI();

		
		
		if(isset($_GET['itemHandle']))
		{
			$report .= 'Item Handle:'.$_GET['itemHandle'].PHP_EOL;

			$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `idInSource` LIKE '".$_GET['itemHandle']."' AND `deleted` IS NULL";
		}
		else 
		{
			
			//$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `field` LIKE 'dc.date.issued' AND `value` LIKE '2013-08' AND `deleted` IS NULL AND idInSource IN (SELECT idInSource FROM metadata WHERE `source` LIKE 'repository' AND `field` LIKE 'dspace.community.handle' AND `value` = '10754/124545' AND `deleted` IS NULL )LIMIT 1";
			$query = "SELECT DISTINCT idInSource FROM metadata WHERE source = 'repository' AND deleted IS NULL AND idInSource NOT IN (
					SELECT idInSource FROM metadata
					WHERE source = 'irts'
					AND field = 'irts.checked.export'
					AND value = 'complete'
					AND deleted IS NULL
					)LIMIT 1";
		}
		
		$handles = getValues($irts, $query, array('idInSource'));
		
		

		foreach($handles as $handle)
		{
			
			//replace / in the handle with underscore
			$itemhandle= preg_replace('/[^A-Za-z0-9_\-]/', '_', $handle);
			
			$export = FALSE;
			
			$itemReport = '';
			
			$dspaceObjectjson = getObjectByHandleFromDSpaceRESTAPI($handle, $token, 'metadata,parentCollectionList,parentCollection');
			
			if(!is_string($dspaceObjectjson))
			{
				$itemReport .= 'first get query error response from DSpace REST API: '.print_r($dspaceObjectjson, TRUE).PHP_EOL;

				sleep(20);

				/* // getting a random bitstream's metadata can help reset the API and overcome internal server errors
				$bitstreamResponse = getBitstreamFromDSpaceRESTAPI('3894853', $token, '?expand=all');

				if(is_string($bitstreamResponse))
				{
					sleep(20);

					$dspaceObject = getObjectByHandleFromDSpaceRESTAPI($handle, $token, 'metadata,parentCollectionList,parentCollection');
				} */
			}
			
			if(is_string($dspaceObjectjson))
			{
				$dspaceObject = json_decode($dspaceObjectjson, TRUE);
				$itemID = $dspaceObject['id'];
				
				$item_dir = "/data/www/irts/bin/preservica_ingest/test/".$itemhandle;
				
				$subfolder = "data";
				$sub_item_dir = "/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder;
				
				if (!is_dir ($item_dir)||is_dir ($item_dir)&&!is_dir ($sub_item_dir)) 
				{
					mkdir("/data/www/irts/bin/preservica_ingest/test/".$itemhandle);
					mkdir("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder);
					
					$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($itemID, $token);
							
					if(is_string($bitstreams))
						{
							// convert the json to array
				            $bitstreams = json_decode($bitstreams, TRUE);
							$first = TRUE;
 
					        foreach ($bitstreams as $bitstream)
							    {
									
									sleep(20);
									
									$bitstreamResponse = getBitstreamFromDSpaceRESTAPI($bitstream['id'], $token, '?expand=all');
								   
								   if(is_string($bitstreamResponse))
								    {
									   $bitstreamResponseArray = json_decode($bitstreamResponse, TRUE);
									   $file_name = $bitstream['name'];
									   $extension = end(explode(".", $file_name));
									   $newfilename= $itemID.'-'.$bitstream['id'].'.'.$extension;		
									   if (!in_array($extension, array('txt','jpg'),true))
									    {
										   $itemReport .= '-- bitstream ID: '.$bitstream['id'].PHP_EOL;
										   
										   //add bitstreams response to item expot folder
										   
										   $bitstreamResponsefile_name = $itemID.'-'.$bitstream['id'].'.'."json";
										   
										   
										   file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$bitstreamResponsefile_name, var_export($bitstreamResponse, TRUE));
										   
										   // add dspace metadata response to export folder - handle.json
										   
										   $MetadataResponsefile = $itemhandle.'.'."json";
										   file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$MetadataResponsefile, var_export($dspaceObjectjson, TRUE));
										   
										   
										   // file url 
										   
										   $FileUrl  = RetrieveBitstreamFromDSpaceRESTAPI($bitstream['id'], $token);
										   echo $bitstream['id'].PHP_EOL;
	
										   if(!empty($bitstreamResponseArray['policies']))
										    {
											   //$first = TRUE;
											   foreach ($bitstreamResponseArray['policies'] as &$policy)
											   
											    {
												
												   sleep(20);
												   
												   //the first items with anonymous read policies should be the main content file
												   
												    if($policy['groupId'] == 0 ||$policy['groupId'] == 20162)
												    {
														
													   if($first) 
													    {
														   
														   //add main file to export folder -internalId-bitstreamId.extension
														   
														   
														   
														     file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder."/".$newfilename,$FileUrl); 
															
															
															 /* $sha1file = hash_file('sha256', "/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder."/".$newfilename);
															 
															 file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder."/hashexample.txt",$sha1file); */
														
														     //add .metadata  file to folder export - OAI dc metadata - main file name 
														     $metadataUrl =(REPOSITORY_OAI_URL.'verb=GetRecord&metadataPrefix=oai_dc&identifier='.REPOSITORY_OAI_ID_PREFIX.$handle);
														
														     $XMLfile = (explode(".", $newfilename));
												             $XMLfile_name = $XMLfile[0].'.'."metadata";
												
												            file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder."/".$XMLfile_name, file_get_contents($metadataUrl));
												
												            //full metadata include provenance and files metadata in xml formate - OAI xoai handle.xml
												
												            $FullmetadataUrl =(REPOSITORY_OAI_URL.'verb=GetRecord&metadataPrefix=xoai&identifier='.REPOSITORY_OAI_ID_PREFIX.$handle);
												             
												
												            $full_XMLfile_name = $itemhandle.'.'."xml";file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$full_XMLfile_name, file_get_contents($FullmetadataUrl));
															
															
												
												         
															$first = FALSE;
														}
														else
														{ 
														file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$subfolder."/".$newfilename,$FileUrl);
														}
														 
												    }
												
												}
										    }
											if(empty($bitstreamResponseArray['policies']))
											{
												file_put_contents("/data/www/irts/bin/preservica_ingest/test/".$itemhandle."/".$newfilename, $FileUrl);
											}
										}
									}
									
								//$export = TRUE;	
								}
							$export = TRUE;
						}
						
					$algos = ['sha1','sha256'];
					foreach ($algos as $algo) 
					{

						foreach (glob($item_dir.DIRECTORY_SEPARATOR."*") as $filename) 
						{
							 if (strtolower(substr($filename, -4)) != ".txt" &&strtolower(substr($filename, -4)) != "data")
								{
									
										$shafile = hash_file($algo,$filename);
										$shaAfile = $algo." checksum of ".$filename."  ".$shafile."\n";$hash_array[] = $shaAfile ;
										var_dump($hash_array);

								}
						}
						foreach (glob($sub_item_dir.DIRECTORY_SEPARATOR."*") as $filename) 
						{
							$shafile = hash_file($algo,$filename);
							$shaAfile = $algo." checksum of ".$filename."  ".$shafile."\n";
							$hash_array[] = $shaAfile ;
							var_dump($hash_array);
						}

						file_put_contents($item_dir."/".$algo.".txt",join("\n", $hash_array));
						$hash_array = array();
					}
					
					foreach ($algos as $algo) 
					{

						foreach (glob($item_dir.DIRECTORY_SEPARATOR."*") as $filename) 
						{
							 if (strtolower(substr($filename, -4)) == ".txt" )
								{
									
										$shafile = hash_file($algo,$filename);
										$shaAfile = $algo." checksum of ".$filename."  ".$shafile."\n";$hash_array[] = $shaAfile ;
										var_dump($hash_array);

								}
						}
						file_put_contents($item_dir."/".$algo.".txt",join("\n", $hash_array),FILE_APPEND);
						$hash_array = array();	
					}

				}
				else 
				{
					
						$empty=true;
						foreach (glob($item_dir.DIRECTORY_SEPARATOR."*") as $file)
						{
						$empty &= is_dir($file) &&rmdir($file);
						}
						rmdir($item_dir);
	
						//RemoveEmptySubFolders($item_dir);
						$itemReport .= 'incomplete export  and folder deleted: '.PHP_EOL.$handle; 
				}
				
			}
			else
			{
				$itemReport .= 'get bitstream list error response from DSpace REST API: '.print_r($bitstreams, TRUE).PHP_EOL;
								
				unset($bitstreams);
				sleep(20);
			}
			$result = saveValue('irts',$handle, 'irts.checked.process', 1, __FUNCTION__, NULL);
			$parentRowID = $result['rowID'];
			if($export)
			{
				$value = 'complete';
				$result = saveValue('irts',$handle, 'irts.checked.export', 1, $value, $parentRowID);
			}
			
			
			if(!empty($itemReport))
			{
			$itemReport = $handle.PHP_EOL.$itemID.PHP_EOL.$itemReport;
				
			$result = saveValue('irts', $handle, 'irts.checked.report', 1, $itemReport, $parentRowID);
			}
						
				
		}
			
	}