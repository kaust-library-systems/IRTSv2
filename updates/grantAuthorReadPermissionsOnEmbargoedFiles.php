<?php
	//Define function to grant authors read permissions on embargoed files
	function grantAuthorReadPermissionsOnEmbargoedFiles($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$recordTypeCounts['bitstreamsChecked'] = 0;
		$recordTypeCounts['emailsSearched'] = 0;
		$recordTypeCounts['epersonsFound'] = 0;
		$recordTypeCounts['bitstreamsWithoutPolicies'] = 0;
		$recordTypeCounts['resourcePolicyCreated'] = 0;
		
		$embargoedOriginalBitstreamUUIDsByHandle = [];

		$bitstreamUUIDsByAuthorOrAdvisorEmail = [];

		$embargoedOriginalBitstreams = getValues($irts, "SELECT `idInSource`, `value` FROM `metadata`
			WHERE `source` LIKE 'repository' 
			AND `field` LIKE 'dspace.bitstream.uuid' 
			AND `added` > '".YESTERDAY."'
			AND deleted IS NULL
			AND parentRowID IN (
				SELECT `rowID` FROM `metadata` WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dspace.bundle.name' 
				AND `value` LIKE 'ORIGINAL'
				AND deleted IS NULL
			) 
			AND `idInSource` IN (
				SELECT `idInSource` FROM `metadata` WHERE `source` LIKE 'repository' 
				AND `field` LIKE 'dc.rights.embargodate' 
				AND `value` > '".TODAY."' 
				AND deleted IS NULL
				)", array('idInSource', 'value'));

		foreach($embargoedOriginalBitstreams as $embargoedOriginalBitstream) {
			$embargoedOriginalBitstreamUUIDsByHandle[$embargoedOriginalBitstream['idInSource']] = $embargoedOriginalBitstream['value'];
		}

		$recordTypeCounts['all'] = count($embargoedOriginalBitstreamUUIDsByHandle);

		foreach($embargoedOriginalBitstreamUUIDsByHandle as $handle => $embargoedOriginalBitstreamUUID) {
			$recordTypeCounts['bitstreamsChecked']++;

			$report .= 'Embargoed original bitstream: '.$handle.' - '.$embargoedOriginalBitstreamUUID.PHP_EOL;

			$kaustPersons = getValues($irts, "SELECT `value` FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `idInSource` LIKE '$handle' 
				AND `field` LIKE 'kaust.person' 
				AND deleted IS NULL", array('value'));

			$orcidAuthors = getValues($irts, "SELECT `value` FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `idInSource` LIKE '$handle' 
				AND `field` LIKE 'orcid.author' 
				AND deleted IS NULL", array('value'));

			foreach($orcidAuthors as $orcidAuthor) {
				if(strpos($orcidAuthor, '::') !== FALSE)
				{
					$name = explode('::', $orcidAuthor)[0];

					$orcid = explode('::', $orcidAuthor)[1];
				}
				else
				{
					$name = $orcidAuthor;

					$orcid = '';
				}
				
				if(in_array($name, $kaustPersons)) {
					if(!empty($orcid))
					{
						$match = checkPerson(array('orcid'=>$orcid, 'name'=>$name));
					}
					else
					{
						$match = checkPerson(array('name'=>$name));
					}
					
					if(!empty($match['email']))
					{
						$email = $match['email'];

						$bitstreamUUIDsByAuthorOrAdvisorEmail[$email][] = $embargoedOriginalBitstreamUUID;
					}
				}
			}

			$orcidAdvisors = getValues($irts, "SELECT `value` FROM `metadata`
				WHERE `source` LIKE 'repository'
				AND `idInSource` LIKE '$handle' 
				AND `field` LIKE 'orcid.advisor' 
				AND deleted IS NULL", array('value'));

			foreach($orcidAdvisors as $orcidAdvisor) {
				if(strpos($orcidAdvisor, '::') !== FALSE) {
					$name = explode('::', $orcidAdvisor)[0];

					$orcid = explode('::', $orcidAdvisor)[1];
				}
				else {
					$name = $orcidAdvisor;

					$orcid = '';
				}
				
				if(!empty($orcid)) {
					$match = checkPerson(array('orcid'=>$orcid, 'name'=>$name));
				}
				else {
					$match = checkPerson(array('name'=>$name));
				}
				
				if(!empty($match['email']))	{
					$email = $match['email'];

					$bitstreamUUIDsByAuthorOrAdvisorEmail[$email][] = $embargoedOriginalBitstreamUUID;
				}
			}
		}

		foreach($bitstreamUUIDsByAuthorOrAdvisorEmail as $email => $bitstreamUUIDs) {
			$response = dspaceGetStatus();
				
			$response = dspaceLogin();

			if($response['status'] == 'success') {
				$recordTypeCounts['emailsSearched']++;

				$report .= PHP_EOL.'Email: '.$email.PHP_EOL;

				$eperson = dspaceSearchForEpersonByEmail($email);

				if($eperson['status'] == 'success') {
					$recordTypeCounts['epersonsFound']++;

					$epersonID = json_decode($eperson['body'], TRUE)['id'];

					$report .= '- epersonID: '.$epersonID.PHP_EOL;

					foreach($bitstreamUUIDs as $bitstreamUUID) {
						$report .= '-- bitstreamUUID: '.$bitstreamUUID.PHP_EOL;

						$existingPolicies = dspaceGetResourcePolicies('resource', $bitstreamUUID);

						if($existingPolicies['status'] == 'success') {
							$existingPolicies = json_decode($existingPolicies['body'], TRUE);

							$existingPolicy = FALSE;

							if(isset($existingPolicies['_embedded']['resourcepolicies'])) {
								foreach($existingPolicies['_embedded']['resourcepolicies'] as $policy) {
									if(!empty($policy['_embedded']['eperson']) && $policy['_embedded']['eperson']['id'] == $epersonID) {
										$report .= '--- Existing policy id: '.$policy['id'].PHP_EOL;
	
										$existingPolicy = TRUE;
									}
								}
	
								if(!$existingPolicy) {
									//create new policy using eperson ID
									$policy = array(
										'name' => 'author access policy',
										'description' => 'Grants authors read access to their own files while the files are embargoed',
										'policyType' => null,
										"action" => "READ",
										"startDate" => null,
										"endDate" => null,
										"type" => "resourcepolicy"
									);
	
									$policyJSON = json_encode($policy);
	
									$response = dspaceCreateResourcePolicy($bitstreamUUID, 'eperson', $epersonID, $policyJSON);
	
									if($response['status'] == 'success')
									{
										$recordTypeCounts['resourcePolicyCreated']++;
										
										$report .= '--- New policy created'.PHP_EOL;
									}
									else
									{
										$report .= '--- Failed to create policy: '.print_r($response, TRUE).PHP_EOL;
									}
								}
								else {
									$report .= '--- Policy already exists'.PHP_EOL;
								}
							}
							else {
								$recordTypeCounts['bitstreamsWithoutPolicies']++;
								$report .= '--- No existing policies'.PHP_EOL;
							}
						}
						else {
							$report .= '- Failed to get existing policies: '.$existingPolicies['body'].PHP_EOL;
						}
					}					
				}
				else {
					$report .= '- Failed to find eperson.'.PHP_EOL;
					//$report .= '- Failed to find eperson: '.print_r($eperson, TRUE).PHP_EOL;
				}
			}
			else
			{
				$report .= 'Failed to log in'.PHP_EOL;
			}
			ob_flush();
			set_time_limit(0);
		}

		$summary = saveReport($irts, __FUNCTION__, $report, $recordTypeCounts, $errors);

		echo $summary;

		return array('changedCount'=>$recordTypeCounts['resourcePolicyCreated'],'summary'=>$summary);
	}
