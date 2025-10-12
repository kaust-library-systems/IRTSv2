<?php
/*

**** This function sets or updates the display fields in a metadata record.

** Parameters :
	$metadata : array to which display fields will be added

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------


function setDisplayFields($metadata)
{
	global $irts;

	$report = '';
	
	$errors = array();

	$patch = [];
	$orcids = [];

	if(isset($metadata['orcid.id']))
	{
		$patch[] = array("op" => "remove",
					 "path" => "/metadata/orcid.id");

		unset($metadata['orcid.id']);
	}

	$templates = [
		'display.details.left' => [
			'License' => ['field' => 'dc.rights.uri'],
			'Embargo End Date' => ['field' => 'dc.rights.embargodate'],
			'Type' => ['field' => 'dc.type'],
			'Patent Status' => ['field' => 'dc.description.status'],
			'Authors' => ['field' => 'dc.contributor.author', 'filter' => 'author'],
			'Advisors' => ['field' => 'dc.contributor.advisor', 'filter' => 'advisor'],
			'Committee Members' => ['field' => 'dc.contributor.committeemember'],
			'Editors' => ['field' => 'dc.contributor.editor'],
			'Assignee' => ['field' => 'dc.contributor.assignee'],
			'Program' => ['field' => 'thesis.degree.discipline', 'filter' => 'program'],
			'KAUST Department' => ['field' => 'dc.contributor.department', 'filter' => 'department'],
			'KAUST Grant Number' => ['field' => 'kaust.grant.number'],
			'Preprint Posting Date' => ['field' => 'dc.date.posted'],
			'Online Publication Date' => ['field' => 'dc.date.published-online'],
			'Print Publication Date' => ['field' => 'dc.date.published-print'],
			'Date' => ['field' => 'dc.date.issued']
		],
		'display.details.right' => [
		    'Summary'=> ['field' => 'display.summary'],
			'Access Restrictions' => ['field' => 'dc.rights.accessrights'],
			'Abstract' => ['field' => 'dc.description.abstract'],
			'Description' => ['field' => 'dc.description'],
			'Citation' => ['field' => 'dc.identifier.citation'],
			'Acknowledgements' => ['field' => 'dc.description.sponsorship'],			
			'Publisher' => ['field' => 'dc.publisher', 'filter' => 'publisher'],
			'Journal' => ['field' => 'dc.identifier.journal', 'filter' => 'journal'],
			'Conference/Event Name' => ['field' => 'dc.conference.name', 'filter' => 'conference'],
			'DOI' => ['field' => 'dc.identifier.doi', 'baseURL' => 'https://doi.org/'],
			'PubMed ID' => ['field' => 'dc.identifier.pmid', 'baseURL' => 'https://www.ncbi.nlm.nih.gov/pubmed/'],
			'PubMed Central ID' => ['field' => 'dc.identifier.pmcid', 'baseURL' => 'https://www.ncbi.nlm.nih.gov/pmc/articles/'],
			'arXiv' => ['field' => 'dc.identifier.arxivid', 'baseURL' => 'https://arxiv.org/abs/'],
			'Patent Number' => ['field' => 'dc.identifier.patentnumber'],
			'Application Number' => ['field' => 'dc.identifier.applicationnumber'],
			'Additional Links' => ['field' => 'dc.relation.url'],
			'Relations' => ['field' => 'display.relations']
		]
	];
	
	foreach($templates as $displayField => $fields)
	{
		if(isset($metadata[$displayField]))
		{
			$patch[] = array("op" => "remove",
						"path" => "/metadata/".$displayField);
		}
		
		$labels =[];
		$displaySnippet = '<span>';

		foreach($fields as $label => $data)
		{
			if(!empty($metadata[$data['field']]))
			{
				$labels[] = $label;
			}
		}
		$lastLabel = array_pop($labels);

		foreach($fields as $label => $data)
		{
			if(!empty($metadata[$data['field']]))
			{
				$displaySnippet .= "<h5>$label</h5>";
				
				foreach($metadata[$data['field']] as $key => $value)
				{
					$orcid = '';

					if(in_array($data['field'], ORCID_ENABLED_FIELDS))
					{
						$fieldParts = explode('.', $data['field']);
						
						$contributorType = array_pop($fieldParts);

						if(isset($value['children']['dc.identifier.orcid'][0]['value']))
						{
							$orcid = $value['children']['dc.identifier.orcid'][0]['value'];
						}
						//metadata coming from DSpace
						elseif(isset($metadata['orcid.'.$contributorType][$key]['value']) && strpos($metadata['orcid.'.$contributorType][$key]['value'], '::') !== FALSE)
						{
							$orcid = explode('::', $metadata['orcid.'.$contributorType][$key]['value'])[1];
						}
						//metadata coming from the IRTS form
						elseif(isset($metadata['orcid.'.$contributorType][$key]) && is_string($metadata['orcid.'.$contributorType][$key]) && strpos($metadata['orcid.'.$contributorType][$key], '::') !== FALSE)
						{
							$orcid = explode('::', $metadata['orcid.'.$contributorType][$key])[1];
						}
						elseif(isset($metadata['dc.identifier.orcid'][$data['field']][$key]))
						{
							$orcid = $metadata['dc.identifier.orcid'][$data['field']][$key];
						}
						elseif($data['field'] == 'dc.contributor.author' && isset($metadata['dc.identifier.orcid'][$key]))
						{
							$orcid = $metadata['dc.identifier.orcid'][$key];

							if(isset($orcid['value']))
							{
								$orcid = $orcid['value'];
							}
						}
					}

					if(isset($value['value']))
					{
						$value = $value['value'];
					}
					
					if(!empty($orcid))
					{
						//remove ORCID prefix if present
						if(strpos($orcid,'https://orcid.org/') !== FALSE)
						{
							$orcid = str_replace('https://orcid.org/', '', $orcid);   
						}
						
						$orcids[] = $orcid;
						
						$searchLink = REPOSITORY_URL.'/search?query=orcid.id:'.$orcid.'&spc.sf=dc.date.issued&spc.sd=DESC';

						$displaySnippet .= '<a href="'.$searchLink.'">'.$value.'</a> <a href="https://orcid.org/'.$orcid.'" target="_blank"><img src="'.ORCID_ICON_URL.'" width="16" height="16"/></a>';
					}
					elseif(!empty($data['filter']))
					{
						$searchLink = REPOSITORY_URL.'/search?spc.sf=dc.date.issued&spc.sd=DESC&f.'.$data['filter'].'='.$value.',equals';
						
						$displaySnippet .= '<a href="'.$searchLink.'">'.$value.'</a>';
					}
					elseif(!empty($data['baseURL']))
					{
						$displaySnippet .= '<a href="'.$data['baseURL'].$value.'">'.$value.'</a>';
					}
					elseif(!empty($data['linkText']))
					{
						$displaySnippet .= '<b><a href="'.$value.'">'.$data['linkText'].'</a></b>';
					}
					elseif(in_array($data['field'], ['dc.description','dc.description.abstract']))
					{
						//standardize use of tags in abstracts
						if($data['field'] == 'dc.description.abstract')
						{
							$oldValue = $value;
							
							$newValue = standardizeTheUseOfTags($value);

							if($oldValue != $newValue)
							{
								$value = $newValue;

								//patch to replace old value
								$patch[] = array("op" => "replace",
											 "path" => "/metadata/".$data['field']."/".$key."/value",
											 "value" => $value);

								//change metadata
								$metadata[$data['field']][$key]['value'] = $value;
							}
						}
						
						$displaySnippet .= str_replace( "\r\n", '<br>', "$value");
					}
					else
					{
						$displaySnippet .= "$value";
					}

					if($key != array_key_last($metadata[$data['field']]))
					{
						$displaySnippet .= "<br>";
					}
				}

				if($label != $lastLabel)
				{
					$displaySnippet .= "<br><br>";
				}
			}
		}
		$displaySnippet .= "</span>";

		//processes creating new records will include the display fields in the initial record
		$metadata[$displayField] = array($displaySnippet);

		//processes updating existing records will include the display fields in the patch
		$patch[] = array("op" => "add",
					 "path" => "/metadata/".$displayField,
					 "value" => array(array("value" => $displaySnippet)));
	}

	foreach($orcids as $orcid)
	{
		$patch[] = array("op" => "add",
					 "path" => "/metadata/orcid.id",
					 "value" => array(array("value" => $orcid)));

		$metadata['orcid.id'][] = array("value" => $orcid);
	}

	return array('report' => $report, 'metadata' => $metadata, 'patch' => $patch);
}
