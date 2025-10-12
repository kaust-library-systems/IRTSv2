<?php
	//Define function to update coral records
	function UpdateBirdsRec($report, $errors, $recordTypeCounts)
	{
		global $irts;
		
		$response = dspaceGetStatus();
				
		//Log in
		$response = dspaceLogin();
		$report = '';
		$errors = array();
		$patch = [];
		//$statuses = array('Breeding resident'=>'Known to have bred at KAUST','Escapes'=>'Birds seen may have been captive birds that escaped','Invasive species'=>'Species is not native to the area, but has become resident','Passage migrant'=>'Stops at KAUST during migratory periods','Summer visitor'=>'Visits KAUST in the summer','Resident in area'=>'Resident in surrounding area but not established within KAUST','Vagrant'=>'Outside of expected range','Winter visitor'=>'Visits KAUST in the winter');

		//$months = array('Jan'=>'January', 'Feb'=>'February', 'Mar'=>'March', 'Apr' => 'April', 'May'=>'May', 'Jun'=>'June', 'Jul'=>'July', 'Aug'=>'August', 'Sep'=>'September', 'Oct'=>'October', 'Nov'=>'November', 'Dec'=>'December');
		 
	
		//$token = loginToDSpaceRESTAPI();
		/* dc.description.notes
		dwc.taxon.vernacularName dwc.taxon.scientificName
		kaust.presence.status
		kaust.presence.abundance
		ebird.media.id
		dc.contributor.photographer
		kaust.presence.month

		$template = array(
			
			'dc.type'=>'Type',
			'dwc.taxon.family'=>'Family',
			'kaust.presence.status'=>'Status in KAUST',
			'kaust.presence.abundance'=>'Abundance in KAUST',
			'dwc.location.locality'=>'Places seen in KAUST',
			'dc.date.collected'=>'Date Collected',
			'dwc.location.decimalLatitude'=>'Latitude',
			'dwc.location.decimalLongitude'=>'Longitude');
			
		$facets = array(
		
		'dwc.location.locality'=>'location');
		
		$Species = array(
		'dwc.taxon.vernacularName'=>'species');
		 */
		$recordTypeCounts['all'] = 1;

	
		 
		 
		 //Get item ID or list of item IDs to check
		 if(isset($_GET['handle']))
		{
			//$report .= 'Item Handle:'.$_GET['handle'].PHP_EOL;

			$query = "SELECT DISTINCT idInSource FROM `metadata` WHERE `source` LIKE 'repository' AND `idInSource` LIKE '".$_GET['handle']."' AND `deleted` IS NULL";

		}
		else
		{
			$query ="SELECT DISTINCT(`idInSource`) FROM `metadata` WHERE `source` LIKE 'repository' AND 
			`field` LIKE 'dc.type' AND `value` LIKE 'Specimen'";
		
		}	
			 
		$Handles = getValues($irts, $query, array('idInSource'));
		//print_r($Handles);
		foreach ($Handles as $itemHandle)
		{
			$report .= 'Item Handle:'.$itemHandle.PHP_EOL;
			$search = dspaceGetItemByHandle($itemHandle);
			$item = json_decode($search['body'], TRUE);
			//print($item);
		    $itemID = $item['id'];
			echo $itemID.PHP_EOL;
			$itemMetadata =$item['metadata'];
			print_r($itemMetadata);
			$record = dSpaceMetadataToArray($itemMetadata);
			//print_r($record);
			
			$itemMetadata['display.details.left'][0]['value'] = '';
			if(isset($itemMetadata['dc.description.notes']))
			{
				$itemMetadata['display.details.left'][0]['value'] .= '<p>'.$itemMetadata['dc.description.notes'][0]['value'].'</p>';
		    }
			
			$itemMetadata['display.details.left'][0]['value'] .= '<table style="table-layout: fixed; width: 100%; margin-left: auto; margin-right: auto;">
			<tbody>';
			
			$itemMetadata['display.details.left'][0]['value'] .= '<tr> <td style="vertical-align: top; width: 50%;">';
			
			$itemMetadata['display.details.left'][0]['value'] .= '<p><h5>'.$itemMetadata['dwc.taxon.vernacularName'][0]['value'];
		
			
			$patches = [];
			
				
				if(!empty($itemMetadata['display.details.left']))
				{
					$patches[] = array("op" => "add",
						"path" => "/metadata/display.details.left/0",
						"value" => array("value" => $itemMetadata['display.details.left']));
					
				}
			
		

				$patchJSON = json_encode($patches);		
				$response = dspacePatchMetadata('items', $itemID, $patchJSON);
				
				$report .= $patchJSON;
				$recordTypeCounts['modified']++;				
				
				
				if($response['status'] == 'success')
				{
					$report .= $itemMetadata['display.details.left'];
					$recordTypeCounts['modified']++;
					//$record = appendProvenanceToMetadata($itemID, $itemMetadata,'updateCoralRecord-forDisplay');
					
				}
				if($response['status'] == 'failed')
				{
					print_r($response);
				}  
				set_time_limit(0);
				ob_flush();	
				
				/* if(isset($simplemetadata['display.details.left']))
				{
					$simplemetadata['display.details.left']= preg_replace('/[\n]+/','', trim($simplemetadata['display.details.left']));
					
					
					
					//processes updating existing records will include the display fields in the patch
					$patch[] = array("op" => "add",
					"path" => "/metadata/display.details.left/0",
					"value" => array("value" => $simplemetadata['display.details.left']));

					$patchJSON = json_encode($patch);
						
					$response = dspacePatchMetadata('items', $itemID, $patchJSON);
					if($response['status'] == 'success')
					{
						$report .= $itemMetadata['display.details.left'][0]['value'];
						$recordTypeCounts['modified']++;
					//$record = appendProvenanceToMetadata($itemID, $itemMetadata,'updateCoralRecord-forDisplay');
					
					}
					if($response['status'] == 'failed')
					{
						print_r($response);
					}

					ob_flush();
					set_time_limit(0);
				} */  
			
					
			ob_flush();
			set_time_limit(0);
			sleep(5);
			
		

		}
		

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
		
	}

					