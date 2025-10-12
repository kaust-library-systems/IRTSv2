<?php
	//Define function to process and store a single entry from the Google Patents csv
	function mapGooglePatentsRecord($input, &$sourceReport)
	{
		global $irts, $errors;
		
		// init 
		$output = array();
		$googlePatentID = $input['googlePatents.id'][0]['value'];
		$source = 'googlePatents';
		$currentFields = array();
		$output['dc.type'][]['value'] = 'Patent';
		
		
	
	
		//$fieldsToSave  = array_keys($fields);
		$googlePatentsFields = array_keys($input);
		
		foreach($googlePatentsFields as $field){
			
			
			$mappedField = mapField($source, $field, '');
			
			if(isset($input[$field])){
				
				if(strpos($field , 'DC.contributor') !== FALSE){
					
					$contributors = array_keys($input[$field]);
					
					foreach($contributors as $key => $contributor ) {
						//re-name the dc.contributor.assignee/dc.contributor.author
						//////////// JSUT TEMP
						$mappedField =  mapField($source, $field, NULL);
						if(strpos($key, 'inventor') !== FALSE) 
							$output[$mappedField] = $input[$field][$contributor];
					
					}
					
					
				} else {
					
					if(!empty($mappedField))
						$output[$mappedField] = $input[$field];
					
				}
				
			}
			
		
		}
		
	
		
		if(array_key_exists('citation_patent_number', $input) !== FALSE  ){
							
			$output['dc.type'][]['value'] = 'Published Application';
			
			// for Consistency: save one ID format
			$output['dc.identifier.patentnumber'][0]['value'] = $googlePatentID;
			
		
		} elseif(array_key_exists('citation_patent_publication_number', $input) !== FALSE) {
			
			$output['dc.type'][]['value'] = 'Granted Patent';
			// for Consistency: save one ID format
			$output['dc.identifier.applicationnumber'][0]['value'] = $googlePatentID;
			

		}
						
						
		// add publisher field
		$publisherStr = 'Patent Office';
		$output['dc.publisher'][]['value'] = $output['dc.description.country'][0]['value'].$publisherStr;
			
		

		$currentFields = array_unique($currentFields);
		
		markExtraMetadataAsDeleted($source, $googlePatentID, NULL, '', '', $currentFields);
		
		return $output;
		
	}
