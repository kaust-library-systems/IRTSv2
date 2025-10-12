<?php
	//Define function to transfer needed data from old source tables to new irts metadata table
	function updateIRTSMetadataFromOldSourceTables($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$source = 'wos';
		
		$items = getValues($irts, "SELECT * FROM repositoryExternalMetadataSources.`wos`", array('UT','DOI'));

		foreach($items as $item)
		{
			$recordTypeCounts['all']++;
			
			$idInSource = $item['UT'];
			
			$report .= $idInSource.PHP_EOL;
			
			if(institutionNameInString($item['Addresses']) || institutionNameInString($item['Funding Text']))
			{			
				$existing = getValues($irts, "SELECT idInSource FROM metadata WHERE source = '$source' AND idInSource = '$idInSource'", array('idInSource'), 'singleValue');
				
				if(!empty($existing))
				{
					$fields = array(
						'DOI'=>'dc.identifier.doi',
						'Author Full Names'=>'dc.contributor.author',
						'Publisher'=>'dc.publisher',
						'Funding Text'=>'dc.description.sponsorship',
						'Conference Title'=>'dc.conference.name',
						'Conference Date'=>'dc.conference.date',
						'Conference Location'=>'dc.conference.location');
					
					$authorsWithAffiliations = array();
					
					$affiliations = explode('; [', $item['Addresses']);
					
					foreach($affiliations as $affiliation)
					{
						$affiliation = str_replace('[','',$affiliation);
						
						$authors = explode('] ', $affiliation)[0];
						
						$affiliation = explode('] ', $affiliation)[1];
						
						if(!empty($affiliation))
						{							
							$authors = explode('; ', $authors);
							
							foreach($authors as $author)
							{
								$authorsWithAffiliations[$author][] = $affiliation;
							}
						}
					}					
					
					foreach($fields as $wosField => $field)
					{
						//echo $wosField.': '.$item[$wosField].PHP_EOL;
						
						if(!empty($item[$wosField]))
						{
							if($wosField === 'Author Full Names')
							{
								$authors = explode('; ',$item[$wosField]);
								
								$place = 1;
								
								foreach($authors as $author)
								{
									$result = saveValue($source, $idInSource, $field, $place, $author, NULL);
									
									$recordTypeCounts[$field.'_'.$result['status']]++;
								
									$report .= ' - '.$field.': '.$author.' - '.$result['status'].PHP_EOL;
									
									$rowID = $result['rowID'];
									
									$place++;
									
									$affiliations = $authorsWithAffiliations[$author];
									
									if(!empty($affiliations))
									{
										$affiliationplace = 1;
										
										foreach($affiliations as $affiliation)
										{
											$result = saveValue($source, $idInSource, 'dc.contributor.affiliation', $affiliationplace, $affiliation, $rowID);
											
											$recordTypeCounts['dc.contributor.affiliation'.'_'.$result['status']]++;
										
											$report .= ' - '.'dc.contributor.affiliation'.': '.$affiliation.' - '.$result['status'].PHP_EOL;
											
											$affiliationplace++;
										}
									}
								}
							}
							else
							{
								$result = saveValue($source, $idInSource, $field, 1, $item[$wosField], NULL);
								
								$recordTypeCounts[$field.'_'.$result['status']]++;
								
								$report .= ' - '.$field.': '.$result['status'].PHP_EOL;
							}
						}
					}
				}
			}
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
