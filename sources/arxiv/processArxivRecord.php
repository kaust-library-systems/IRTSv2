<?php
	//Define function to process arXiv results
	function processArxivRecord($item)
	{
		global $irts, $report;

		$source = 'arxiv';

		$record = array();

		$arxivURL = $item->id;
		$arxivIDParts = explode('v', str_replace("http://arxiv.org/abs/", "", $arxivURL));
		$arxivID = $arxivIDParts[0];
		$arxivVersion = $arxivIDParts[1];

		//echo ' -- '.$arxivID.PHP_EOL;

		//Save copy of item XML
		$xml = $item->asXML();

		$result = saveSourceData($irts, $source, $arxivID, $xml, 'XML');

		$recordType = $result['recordType'];

		//Set dc.type as "Preprint" for all arxiv records
		$record['dc.type'][]['value'] = 'Preprint';

		//Set dc.publisher as "arXiv" for all arxiv records
		$record['dc.publisher'][]['value'] = 'arXiv';

		//Save version number
		$record['dc.version'][]['value'] = $arxivVersion;

		$fieldPlace = array();
		foreach($item->children() as $element)
		{
			$field = mapField($source, $element->getName(), '');
			$value = transform($source, $field, $element, trim((string)$element));
			$record[$field][]['value'] = $value;

			if(count($element->children())!==0 || count($element->attributes())!==0)
			{
				//Set values in case there are child elements or attributes
				$parentField = $field;

				if(count($element->children())!==0)
				{
					foreach($element->children() as $childElement)
					{
						$childField = mapField($source, $childElement->getName(), $parentField);
						$childValue = transform($source, $childField, $childElement, trim((string)$childElement));
						$record[$parentField][count($record[$parentField])-1]['children'][$childField][]['value'] = $childValue;
					}
				}

				if(count($element->attributes())!==0)
				{
					foreach($element->attributes() as $childField => $childValue)
					{
						$attributesToIgnore = array('scheme','term');
						if(!in_array($childField, $attributesToIgnore))
						{
							$childField = mapField($source, $childField, $parentField);
							$childValue = transform($source, $childField, $element, trim($childValue));
							$record[$parentField][count($record[$parentField])-1]['children'][$childField][]['value'] = $childValue;
						}
					}
				}
			}
		}

		//Save record
		$report .= saveValues($source, $arxivID, $record, NULL);

		return array('idInSource'=>$arxivID,'recordType'=>$recordType);
	}
