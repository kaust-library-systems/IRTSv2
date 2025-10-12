<?php

//iterate over ncbi bisample record 
function iterateOverBioSampleFields($record, $source, $values, $keys=array())
{
	global $irts;

	
			foreach ($values as $key => $value)
	{
		if(is_string($value))
		{
			if(is_int($key))
			{
				$field =  $source.'.'.implode('.',$keys);
			}
			else
			{
				$field = $source.'.'.implode('.',$keys).'.'.$key;
			}
			
			
				if(strpos($field,'publication_date') !== FALSE)
			{
				$field = 'dc.date.issued';
				$value = explode('T', $value)[0];
			}
			
			if($field === 'ncbi.BioSample.last_update')
			{
				 $field = 'dc.date.updated';
				$value = explode('T', $value)[0];
			}
			if($field === 'ncbi.BioSample.submission_date')
			{
				 $field = 'dc.date.submitted';
				$value = explode('T', $value)[0];
			}
			if(strpos($field,'ncbi.BioSample.accession') !== FALSE)
			{
				$field = 'dc.identifier.biosample';
				$value = $value;
			}
			if(strpos($field,'ncbi.BioSample.Description.Title') !== FALSE)
			{
				$field = 'dc.Title';
				$value = $value;
			}
			if($field === 'ncbi.BioSample.Description.Organism.taxonomy_name')
			{
				$field = 'dwc.taxon.scientificName';
				$value = $value;
			}
			 
			
			//echo $field.PHP_EOL;
			
			$field = mapField($source, $field, '');
			
			//echo $field.PHP_EOL;

			// save the value
			if(!empty(trim($value)))
			{
				$record[$field][]['value'] = $value;
			}
		}
		else
		{
			if(!is_int($key)&&$key !== '@attributes')
			{
				$keys[] = $key;
			}

			//print_r($value);
			$record = iterateOverBioSampleFields($record, $source, $value, $keys);
			
			if(!is_int($key)&&$key !== '@attributes')
			{
				array_pop($keys);
			}
		}
	}

	return $record;
} 
