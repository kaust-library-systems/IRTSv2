<?php
/*

**** This file defines a function to add ORCIDs to metadata based on local person matches.

** Parameters :
	$record: an associative array with the item metadata.

*/

//--------------------------------------------------------------------------------------------

function addORCIDs($record)
{
	global $irts;
	
	$report = '';

	$metadata = [];
	
	$orcidAuthors = [];
	$orcidAdvisors = [];
	$orcidCommitteeMembers = [];

	foreach($record as $field => $entries)
	{
		foreach($entries as $place => $entry)
		{
			$newEntry = [];

			$newEntry['value'] = $entry['value'];

			if(in_array($field, ['dc.contributor.author', 'dc.contributor.advisor', 'dc.contributor.committeemember']))
			{
				$orcid = '';
				
				//names with whitespace at the beginning or end will not match the local database
				$name = trim($entry['value']);
				
				if($field == 'dc.contributor.author')
				{
					//from direct entry in thesis submission form
					$orcid = $record['dc.identifier.orcid'][$place]['value'];

					//remove ORCID prefix if present
					if(strpos($orcid,'https://orcid.org/') !== FALSE)
					{
						$orcid = str_replace('https://orcid.org/', '', $orcid);   
					}
				}

				if(empty($orcid))
				{
					$match = checkPerson(array('name'=>$name));
					//print_r($match);

					if(!empty($match['localID']))
					{
						//use the standard name from the database in case the match was based on a variant
						$name = $match['controlName'];
						
						if($field == 'dc.contributor.advisor' || $field == 'dc.contributor.committeemember')
						{
							//only accept match if person is faculty
							$profTitle = getValues($irts, "SELECT value
							FROM metadata
							WHERE source = 'local'
							AND idInSource = '".$match['localID']."'
							AND field = 'local.employment.type'
							AND value LIKE 'Faculty'
							AND deleted IS NULL", array('value'), 'singleValue');

							if(!empty($profTitle))
							{
								//add ORCID from local person match
								if(!empty($match['orcid']))
								{
									$orcid = $match['orcid'];
								}
							}
							else
							{
								$report .= '- Person name "'.$name.'" is not a KAUST faculty member name, so their ORCID was not added to the metadata.<br>'.PHP_EOL;
							}
						}
						else
						{						
							//add ORCID from local person match
							if(!empty($match['orcid']))
							{
								$orcid = $match['orcid'];
							}
						}
					}
					else
					{
						$report .= '- Person name "'.$name.'" was not found in the local person database, so their ORCID was not added to the metadata.<br>'.PHP_EOL;
					}
				}
				
				if(!empty($orcid))
				{
					if($field == 'dc.contributor.advisor')
					{
						$orcidAdvisors[$place] = $name . '::' . $orcid;
					}
					elseif($field == 'dc.contributor.committeemember')
					{
						$orcidCommitteeMembers[$place] = $name . '::' . $orcid;
					}
					elseif($field == 'dc.contributor.author')
					{
						$orcidAuthors[$place] = $name . '::' . $orcid;
					}
				}
				else
				{
					if($field == 'dc.contributor.author')
					{
						//keep author names even when there is no ORCID so that place stays consistent with dc.contributor.author
						$orcidAuthors[$place] = $name;
					}
					elseif($field == 'dc.contributor.advisor')
					{
						//keep advisor names even when there is no ORCID so that place stays consistent with dc.contributor.advisor
						$orcidAdvisors[$place] = $name;
					}
					elseif($field == 'dc.contributor.committeemember')
					{
						//keep committee member names even when there is no ORCID so that place stays consistent with dc.contributor.committeemember
						$orcidCommitteeMembers[$place] = $name;
					}
				}
				$newEntry['value'] = $name;

				if(!empty($orcid))
				{
					$newEntry['children']['dc.identifier.orcid'][]['value'] = $orcid;
				}
			}

			$metadata[$field][] = $newEntry;
		}
	}

	$patches = [];

	//remove dc.identifier.orcid field, this will only be used during submission for ETDs
	if(isset($metadata['dc.identifier.orcid']))
	{
		unset($metadata['dc.identifier.orcid']);
		
		$patches[] = array("op" => "remove",
					"path" => "/metadata/dc.identifier.orcid");
	}

	if(!empty($orcidAuthors))
	{
		if(isset($metadata['orcid.author']))
		{
			unset($metadata['orcid.author']);
			
			$patches[] = array("op" => "remove",
						"path" => "/metadata/orcid.author");
		}
		
		foreach($orcidAuthors as $place => $orcidAuthor)
		{
			$patches[] = array("op" => "add",
			"path" => "/metadata/orcid.author/-",
			"value" => array("value" => $orcidAuthor));

			$metadata['orcid.author'][] = array("value" => $orcidAuthor);
		}
	}

	if(!empty($orcidAdvisors))
	{
		if(isset($metadata['orcid.advisor']))
		{
			unset($metadata['orcid.advisor']);
			
			$patches[] = array("op" => "remove",
						"path" => "/metadata/orcid.advisor");
		}
		
		foreach($orcidAdvisors as $place => $orcidAdvisor)
		{
			$patches[] = array("op" => "add",
			"path" => "/metadata/orcid.advisor/-",
			"value" => array("value" => $orcidAdvisor));

			$metadata['orcid.advisor'][] = array("value" => $orcidAdvisor);
		}
	}

	if(!empty($orcidCommitteeMembers))
	{
		if(isset($metadata['orcid.committeemember']))
		{
			unset($metadata['orcid.committeemember']);
			
			$patches[] = array("op" => "remove",
						"path" => "/metadata/orcid.committeemember");
		}
		
		foreach($orcidCommitteeMembers as $place => $orcidCommitteeMember)
		{
			$patches[] = array("op" => "add",
			"path" => "/metadata/orcid.committeemember/-",
			"value" => array("value" => $orcidCommitteeMember));

			$metadata['orcid.committeemember'][] = array("value" => $orcidCommitteeMember);
		}
	}

	// return the metadata, patches, and report
	return array('metadata'=>$metadata, 'patches'=>$patches, 'report' => $report);
}