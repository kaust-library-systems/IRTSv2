<?php
/*

**** This function takes the Scopus XML and adds individual item ids and metadata to the entries list

** Parameters :
	$xml = original Scopus XML
	$entries = the list of Scopus items
	$harvestBasis = optional label to assign so that processors know what type of query returned a given item

*/

//--------------------------------------------------------------------------------------------

function addToScopusList($xml, $entries, $harvestBasis = NULL)
{
	global $irts;
	
	//Strip namespaces due to problems in accessing elements with namespaces even with xpath, temporary solution?
	$xml = str_replace('dc:', '', $xml);
	$xml = str_replace('opensearch:', '', $xml);
	$xml = str_replace('prism:', '', $xml);

	$xml = simplexml_load_string($xml);

	if((int)$xml->totalResults !== 0)
	{
		foreach($xml->entry as $item)
		{
			$eid = '';
			$eid = (string)$item->eid;

			if(!isset($entries[$eid]))
			{
				//NULL harvest basis will be ignored later in addToProcess
				$entries[$eid]['harvestBasis'] = $harvestBasis;
			}
		}
	}

	return $entries;
}
