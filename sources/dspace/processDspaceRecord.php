<?php	
	//Define function to process DSpace JSON metadata for a single repository item and return the metadata as an array in the IRTS format
	function processDspaceRecord($json)
	{
		$report = '';
		
		$source = 'dspace';
		
		$record = json_decode($json, TRUE);

		$metadata = array();

		//$report .= print_r($record, TRUE);

		foreach($record as $key => $value)
		{
			if($key == 'metadata')
			{
				$metadata = array_merge($metadata, $value);

				foreach(ORCID_ENABLED_FIELDS as $contributorField)
				{
					if(isset($metadata[$contributorField]))
					{
						$orcidField = str_replace('dc.contributor', 'orcid', $contributorField);
						
						if(isset($metadata[$orcidField]))
						{
							foreach($metadata[$contributorField] as $place => $contributor)
							{
								$orcidContributor = $metadata[$orcidField][$place]['value'];

								if(strpos($orcidContributor, '::') !== FALSE)
								{
									$orcidContributorParts = explode('::', $orcidContributor);

									$orcidContributorName = $orcidContributorParts[0];

									$orcid = $orcidContributorParts[1];

									if($orcidContributorParts[0] == $contributor['value'])
									{
										//orcid will be saved as child row in metadata table, this is what is expected and used by IOI
										$metadata[$contributorField][$place]['children']['dc.identifier.orcid'][0]['value'] = $orcid;
									}
								}
							}
						}
					}
				}
			}
			elseif($key == '_links')
			{
				//skip for now
			}
			elseif($key == '_embedded')
			{
				$bundles = $value['bundles']['_embedded']['bundles'];

				foreach($bundles as $place => $bundle)
				{
					$metadata['dspace.bundle'][$place]['value'] = $bundle['name'];

					foreach($bundle as $key => $value)
					{
						if($key == 'metadata')
						{
							$metadata['dspace.bundle'][$place]['children'] = $value;
						}
						elseif($key == '_links')
						{
							//skip for now
						}
						elseif($key == '_embedded')
						{
							$bitstreams = $value['bitstreams']['_embedded']['bitstreams'];

							foreach($bitstreams as $place => $bitstream)
							{
								$metadata['dspace.bundle'][$place]['children']['dspace.bitstream'][$place]['value'] = $bitstream['uuid'];

								foreach($bitstream as $key => $value)
								{
									if($key == 'metadata')
									{
										$metadata['dspace.bundle'][$place]['children']['dspace.bitstream'][$place]['children'] = $value;
									}
									elseif($key == '_links')
									{
										//skip for now
									}
									elseif(!empty($value) && !is_array($value)) //checksum is array and will be ignored
									{
										//this saves all other bitstream-level fields (such as UUID) as metadata in the dspace namespace
										$metadata['dspace.bundle'][$place]['children']['dspace.bitstream'][$place]['children']['dspace.bitstream.'.$key][] = array('value'=>$value);
									}
								}
							}
						}
						elseif(!empty($value) && !is_array($value))
						{
							//this saves all other bundle-level fields (such as UUID) as metadata in the dspace namespace
							$metadata['dspace.bundle'][$place]['children']['dspace.bundle.'.$key][] = array('value'=>$value);
						}
					}
				}
			}
			else
			{
				//this saves all other item-level fields (such as UUID) as metadata in the dspace namespace

				//flags will be saved as TRUE or FALSE
				if(!$value)
				{
					$value = 'FALSE';
				}

				$metadata['dspace.'.$key][] = array('value'=>$value);
			}
		}

		return array('record' => $metadata, 'report' => $report);
	}
