<?php	
	//Define function to update local org unit records with details from the old departments table
	function syncLocalOrgs($source)
	{
		global $irts, $errors;

		$report = '';

		$recordTypeCounts = array(
			'all'=>0, 
			'existingOrgs'=>0, 
			'orgsInFile'=>0, 
			'endDateAddedToExistingOrgs'=>0,
			'orgTypeChanged'=>0,
			'orgParentChanged'=>0,
			'orgNameChanged'=>0,
			'newOrgInFile'=>0,
			'new'=>0,
			'modified'=>0,
			'unchanged'=>0,
			'skipped'=>0
		);

		//array to hold list of current orgs
		$orgs = [];

		$orgIDs = getValues(
			$irts, 
			"SELECT `value` FROM `metadata` 
				WHERE `source` LIKE '$source' 
				AND `idInSource` LIKE 'org_%' 
				AND `field` LIKE 'local.org.id' 
				AND `deleted` IS NULL ", 
			array('value'), 
			'arrayOfValues');

		foreach($orgIDs as $orgID)
		{
			//retrieve details of current orgs from the database
			$orgs[$orgID] = getRecord($source, 'org_'.$orgID, 'flat');
		}

		$report .= 'Existing orgs in database: '.count($orgs).PHP_EOL;
		$recordTypeCounts['existingOrgs'] = count($orgs);

		//get orgs from the file
		$result = getOrgsFromFile();

		$orgsInFile = $result['orgsInFile'];
		$fileDate = $result['fileDate'];
		$report .= $result['report'];

		if(empty($orgsInFile))
		{
			$errors[] = 'No orgs found in file';
		}
		elseif(empty($fileDate))
		{
			$errors[] = 'No Organizations file found';
		}
		else //only process if a new Organizations file was found with entries
		{
			$recordTypeCounts['orgsInFile'] = count($orgsInFile);
		
			$report .= PHP_EOL.'- Check if existing orgs are still in the file -'.PHP_EOL;

			//go through existing orgs and update details
			foreach($orgs as $orgID => $org)
			{
				//unset ignored fields
				unset($org['local.org.alternateID']);
				unset($org['local.name.variant']);
				unset($org['local.address.variant']);
				
				//print_r($org);

				//if an org without an end date is not in the file, give it the fileDate as an end date
				if(!isset($orgsInFile[$orgID]) && !isset($org['local.date.end']))
				{
					$org['local.date.end'] = $fileDate;

					$report .= $orgID.') '.$org['local.org.name'].' -- existing org no longer in file - adding end date'.PHP_EOL;

					$recordTypeCounts['endDateAddedToExistingOrgs']++;
				}

				//if an org is in the file and has an end date, remove the end date
				if(isset($orgsInFile[$orgID]) && isset($org['local.date.end']))
				{
					unset($org['local.date.end']);

					$report .= $orgID.') '.$org['local.org.name'].' -- existing org in file - removing end date'.PHP_EOL;
				}

				//if an org is in the file and the type, parent or name has changed, update the org
				if(isset($orgsInFile[$orgID]))
				{
					$orgInFile = $orgsInFile[$orgID];

					//if the org type has changed, update it
					if($org['local.org.type'] !== $orgInFile['local.org.type'])
					{
						$report .= $orgID.') '.$org['local.org.name'].' -- org type changed from: '.$org['local.org.type'].' to: '.$orgInFile['local.org.type'].PHP_EOL;

						$org['local.org.type'] = $orgInFile['local.org.type'];

						$recordTypeCounts['orgTypeChanged']++;
					}

					//if the parent has changed, update it
					if(!isset($org['local.org.parent']))
					{
						$report .= $orgID.') '.$org['local.org.name'].' -- existing org has no parent'.PHP_EOL;
					}
					elseif($org['local.org.parent'] !== $orgInFile['local.org.parent'])
					{
						$report .= $orgID.') '.$org['local.org.name'].' -- parent changed from: '.$org['local.org.parent'].' to: '.$orgInFile['local.org.parent'].PHP_EOL;

						$org['local.org.parent'] = $orgInFile['local.org.parent'];

						$recordTypeCounts['orgParentChanged']++;
					}

					//if the name has changed, and the org type is not 'corelab', 'program', or 'researchcenter', update it
					if($org['local.org.name'] !== $orgInFile['local.org.name'] && !in_array($org['local.org.type'], array('corelab', 'program', 'researchcenter')))
					{
						$report .= $orgID.') '.$org['local.org.name'].' -- name changed from: '.$org['local.org.name'].' to: '.$orgInFile['local.org.name'].PHP_EOL;

						$org['local.org.name'] = $orgInFile['local.org.name'];

						$recordTypeCounts['orgNameChanged']++;
					}
				}

				$orgs[$orgID] = $org;
			}

			$report .= PHP_EOL.'- Check if orgs in file are marked as alternate IDs for existing orgs or if they are new orgs -'.PHP_EOL;

			//go through orgs in the file and add them if they are not already in the database
			foreach($orgsInFile as $orgID => $orgInFile)
			{
				$alternateID = getValues(
					$irts, 
					"SELECT `idInSource` FROM `metadata` 
						WHERE `source` LIKE '$source' 
						AND `field` LIKE 'local.org.alternateID' 
						AND `value` LIKE '$orgID' 
						AND `deleted` IS NULL",
					array('idInSource'), 
					'singleValue'
				);

				if(!empty($alternateID))
				{
					$recordTypeCounts['skipped']++;

					//$report .= $orgID.') '.$orgInFile['local.org.name'].' -- skipped - known alternate ID for '.$alternateID.PHP_EOL;
				}
				else
				{
					//if the org is not in the list of existing orgs, add it
					if(!isset($orgs[$orgID]))
					{
						$recordTypeCounts['newOrgInFile']++;
						
						// the start date for new orgs is the date they first appeared in the daily file
						$orgInFile['local.date.start'] = $fileDate;

						$report .= $orgID.') '.$orgInFile['local.org.name'].' -- new'.PHP_EOL;

						//add new org from file to the list of orgs
						$orgs[$orgID] = $orgInFile;
					}
				}
			}

			//save orgs to the database
			foreach($orgs as $orgID => $org)
			{
				$recordTypeCounts['all']++;
				$orgID = 'org_'.$orgID;

				//set visibility
				$org['local.org.visibility'] = setOrgVisibility($org['local.org.type'], $org['local.org.id']);

				//sort the org fields alphabetically, so the order of fields is the same for all orgs (this way source data will not appear modified just because the field order changed)
				ksort($org);
				
				$sourceDataAsJSON = json_encode($org);
					
				//Save copy of item JSON
				$result = saveSourceData($irts, $source, $orgID, $sourceDataAsJSON, 'JSON');

				$recordType = $result['recordType'];
				
				$recordTypeCounts[$recordType]++;

				/* $report .= PHP_EOL.'- '.$orgID.' -'.PHP_EOL;

				$report .= ' - org source data status: '.$recordType.PHP_EOL;
				
				//save values for the org, fields that are added manually will be ignored and not removed even though they are not in the new record
				$functionReport = saveValues($source, $orgID, $org, NULL, array('local.org.alternateID', 'local.name.variant', 'local.address.variant')); */

				//print_r($org);

				if($recordType !== 'unchanged')
				{
					$report .= PHP_EOL.'- '.$orgID.' -'.PHP_EOL;

					$report .= ' - org source data status: '.$recordType.PHP_EOL;
					
					//save values for the org, fields that are added manually will be ignored and not removed even though they are not in the new record
					$functionReport = saveValues($source, $orgID, $org, NULL, array('local.org.alternateID', 'local.name.variant', 'local.address.variant'));

					//$report .= $functionReport;
				}
			}
		}

		echo $report.PHP_EOL;

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		//echo $summary;

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
