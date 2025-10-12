<?php
	//Define function to process Semantic Scholar results
	function processSemanticScholarRecord($item)
	{
		global $irts, $report;

		$source = 'semanticScholar';

		$idInSource = $item['paperId'];

		//Save copy of item XML
		$json = json_encode($item);

		$result = saveSourceData($irts, $source, $idInSource, $json, 'JSON');
					
		$recordType = $result['recordType'];

		foreach($item as $field => $value)
		{
			if(!is_null($value))
			{			
				if($field === 'authors')
				{
					$place = 0;
					foreach($value as $author)
					{
						$field = 'dc.contributor.author';
						
						$authorRowID = mapTransformSave($source, $idInSource, '', $field, '', $place, $author['name'], NULL);
						
						$field = 'dc.identifier.semanticScholarAuthorID';
						
						$rowID = mapTransformSave($source, $idInSource, '', $field, '', 0, $author['authorId'], $authorRowID);
						
						$place++;
					}
				}
				elseif($field === 'externalIds')
				{
					foreach($value as $idField => $idValue)
					{
						$field = 'dc.identifier.'.$idField;
						
						$rowID = mapTransformSave($source, $idInSource, '', $idField, '', 0, $idValue, NULL);
					}
				}
				elseif($field === 'topics')
				{
					/* $place = 1;
					foreach($value as $subject)
					{
						$rowID = mapTransformSave($source, $idInSource, '', $field, '', 0, $value, NULL);
						
						$place++;
					} */
				}
				else
				{
					$rowID = mapTransformSave($source, $idInSource, '', $field, '', 0, $value, NULL);
				}
			}			
		}

		return array('idInSource'=>$idInSource,'recordType'=>$recordType);
	}
