<?php
/*

**** This file is responsible sending emails about articles that in publishers agreements.

** Parameters :
	




** Output : no required


** Created by : Yasmeen Alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 07 July 2021 - 2:08 PM

*/

//--------------------------------------------------------------------------------------------------
function sendPaEmail($report, $errors, $recordTypeCounts){
	

	global $irts;
	
	// init 
	$errors = array();
	$recordTypeCounts['matched'] = 0;
	$recordTypeCounts['unmatched'] = 0;
	$recordTypeCounts['sent'] = 0;
	$recordTypeCounts['silent notification'] = 0;
	$recordTypeCounts['unmatched condition'] = 0;
	$recordTypeCounts['unmatched publisher'] =0;
	$agreementRowID = 0;
	$report = '';
	
	
	
	if(isset($_GET['date'])) {
		
		$date = $_GET['date'];
	}
	else {
		
		$date = YESTERDAY;
	}
	
	
	
	$sendPerPublisher = array();


	// get all YESTERDAY's articles
	$handles = getValues($irts, "SELECT idInSource FROM `metadata` where source = 'repository' AND field = 'dc.type' AND value ='Article' AND idInSource IN ( SELECT idInSource FROM `metadata` where source = 'repository' AND  field='dc.date.accessioned' AND value LIKE '".$date."%' AND `deleted` IS NULL ) AND `deleted` IS NULL", array('idInSource'), 'arrayOfValues');
	
	//gell all the agreements that still running with the conditions 
	$publishers = getValues($irts, "SELECT value FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.publisher' AND `rowID` IN ( SELECT parentRowID FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.agreement' AND `rowID` IN( SELECT `parentRowID` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.date.end' AND VALUE > '".$date."%' AND `deleted` IS NULL ) AND `deleted` IS NULL ) AND `deleted` IS NULL", array('value'), 'arrayOfValues');
	

	
	// for each article 
	foreach($handles as $handle){
		
		// reset
		$send = FALSE;
		$correspondingAuthors = '';
		$correspondingAuthors = array();
		
		// check if the article from one of the publisher
		$articlePublisher = getValues($irts, "SELECT value FROM `metadata` where source = 'repository' AND field = 'dc.publisher' AND idInSource ='".$handle."' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		// get the DOI
		$articleDOI = getValues($irts, "SELECT value FROM `metadata` where source = 'repository' AND field = 'dc.identifier.doi' AND idInSource ='".$handle."' AND `deleted` IS NULL", array('value'), 'singleValue');
		
		$recordTypeCounts['all']++;
		
		// lower case publication
		$articlePublisher = strtolower($articlePublisher);
		
	
		// if the article came from one of the publisher that we had an agreement with
		if(in_array($articlePublisher, $publishers)){
			
			
			
			// get the condition of that publisher
			$agreementRowID = getValues($irts, "select `rowID` from metadata where field = 'pa.agreement' AND `parentRowID` = ( SELECT `rowID` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.publisher' AND value = '".$articlePublisher."' AND `deleted` IS NULL ) AND `deleted` IS NULL ", array('rowID'), 'singleValue');
			
			// check the notification 
			$notification = getValues($irts, "SELECT `value` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.notification' AND parentRowID = '".$agreementRowID ."' AND `deleted` IS NULL", array('value'), 'singleValue');
			
			if(!empty($notification)){
				
					// save the agreemntRowID for this publisher
					$sendPerPublisher[$articlePublisher]['agreementRowID'] = $agreementRowID;
		
					// reset
					$articleDetails = '';
					
					$eligibleauthors = getValues($irts, "SELECT `value` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.eligibleauthors' AND parentRowID = '".$agreementRowID ."' AND `deleted` IS NULL", array('value'), 'singleValue');
					
					// echo $condition .'<br>';
					//get the article's authors 
					$authors =  getValues($irts, "SELECT `rowID`, value FROM `metadata` where source = 'irts' AND `idInSource` = ( SELECT `idInSource` FROM `metadata` where source = 'irts' AND field = 'dc.identifier.doi' AND value = '".$articleDOI."' AND `deleted` IS NULL limit 1 ) AND field = 'dc.contributor.author' AND `deleted` IS NULL", array('rowID', 'value'), 'arrayOfValues');
					
					
					if( in_array($eligibleauthors, array('First corresponding author', 'Any corresponding author')) ){
							
							// for each author 
							
							foreach($authors as $index => $author){
								
								// get the email 
								$email = getValues($irts, "SELECT value FROM `metadata` where source = 'irts' AND field = 'irts.author.correspondingEmail' AND parentRowID = '".$author['rowID']."' AND `deleted` IS NULL", array('value'), 'singleValue');
								
								
								//if the email is from KAUST
								if(strpos($email, 'kaust.edu.sa') !== FALSE){
									
									array_push($correspondingAuthors, $author['value']);
									// check the index if it's 0 this means it's the First corresponding author
									if(strpos($eligibleauthors, 'First corresponding author') !== FALSE  && $index == 0 ){
										
										$send = TRUE;
										break;
										
									} elseif( strpos( $eligibleauthors, 'First corresponding author') !== FALSE  && $index != 0 ){
										
										// if the condition First corresponding author no need to loop over all the authors 
										// if the first match not a kaust First corresponding author
										break;
										
										
									} elseif( strpos($eligibleauthors, 'Any corresponding author') !== FALSE  ) {
										
										$send = TRUE;
										break;
									}
									
								}
								
							}

						} elseif(strpos($eligibleauthors, 'Any author') !== FALSE) {
							
							foreach($authors as $author){
								
								$affiliation = getValues($irts, "SELECT value FROM `metadata` where source = 'irts' AND field = 'dc.contributor.affiliation' AND parentRowID = '".$author['rowID']."' AND `deleted` IS NULL", array('value'), 'singleValue');
								
								if(institutionNameInString($affiliation)){
									
									// if one matched exit
									$send = TRUE;
									break;
									
								}
								
							}

						}
						
					if($send) {
					
							
							
							$authors = $correspondingAuthors;
							
							if( empty($authors))
								$authors = array('None of the corresponding authors are from KAUST');
								
								
							
							#getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.contributor.author'), array('value'), 'arrayOfValues');
							
							$title = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.title'), array('value'), 'singleValue');
							
							$volume = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.volume'), array('value'), 'singleValue');
							
							$journaltitle = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.journal'), array('value'), 'singleValue');
							
							$issn = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.issn'), array('value'), 'singleValue');
							
							$issue = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.issue'), array('value'), 'singleValue');
							
							$issuedDate = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.date.issued'), array('value'), 'singleValue');
							
							$accepted = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.date.accepted'), array('value'), 'singleValue');
							
							
							$doi = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.identifier.doi'), array('value'), 'singleValue');
							
							
							$license =  getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dc.rights.uri'), array('value'), 'singleValue');
				
							
							$file = getValues($irts, setSourceMetadataQuery('repository', $handle, NULL, 'dspace.bitstream.url'), array('value'), 'singleValue');
							
							if(isset($sendPerPublisher[$articlePublisher]['articleDetails'] ))
								$articleDetails = $sendPerPublisher[$articlePublisher]['articleDetails'] ;
							
							$articleDetails  .= "
									 <tr>
									<td align='center' style='border:1px solid #333;'>".implode("; ", $authors)."</td>
									<td  align='center' style='border:1px solid #333; '> ".$title."</td> 
									<td  align='center' style='border:1px solid #333;'> ".$journaltitle."</td>
									<td  align='center' style='border:1px solid #333;'>  ".$issn."</td>
									<td  align='center' style='border:1px solid #333;'>  ".$volume."</td>
									<td  align='center' style='border:1px solid #333;'> ".$issue."</td>
									<td  align='center' style='border:1px solid #333;'> <a href='https://doi.org/$doi'>".$doi."</a></td>
									<td align='center' style='border:1px solid #333;'>  ".$accepted."</td>
									<td align='center' style='border:1px solid #333;' > ".$issuedDate."</td>
									<td align='center' style='border:1px solid #333;' > ".$license."</td>
									<td align='center' style='border:1px solid #333;' ><a href='$file'>Link</a></td>
									 </tr>
									";
				
							 $sendPerPublisher[$articlePublisher]['articleDetails'] = $articleDetails;

						} else {
							
							// unmatched article
							$report .= '- Unmatched condition handle: '.$handle.PHP_EOL;
							$recordTypeCounts['unmatched condition']++;
						}
				
				} else {
					
					$report .= '- Silent Notification: '.$handle.PHP_EOL;
					$recordTypeCounts['silent notification']++;
					
				}
						
		} else {
			
			// unmatched article
			$report .= '- Unmatched publisher: '.$articlePublisher.PHP_EOL;
			$recordTypeCounts['unmatched publisher']++;
			
		}
		
	}
		
	// send per publisher
	if(!empty($sendPerPublisher) ){
		
		
		$to = PUBLISHER_AGREEMENT_NOTICE_RECIPIENT['email'];
		
			
		foreach($sendPerPublisher as $publisher => $value) {
		
			// if the publisher has a article to be send
			$message = "Dear ".PUBLISHER_AGREEMENT_NOTICE_RECIPIENT['name'].",<br>";
		
			$table  = "
					 <tr>
					<th align='center' style='border:1px solid #333;'> Corresponding author(s)</th>
					<th align='center' style='border:1px solid #333;'> Title</th> 
					<th align='center' style='border:1px solid #333;'> Journal name</th>
					<th align='center' style='border:1px solid #333;'> Journal online ISSN</th>
					<th align='center' style='border:1px solid #333;'> Volume number</th>
					<th align='center' style='border:1px solid #333;'> Issue number</th>
					<th align='center' style='border:1px solid #333;'>DOI</th>
					<th align='center' style='border:1px solid #333;'> Acceptance date</th>
					<th align='center' style='border:1px solid #333;'> Date of online publication</th>
					<th align='center' style='border:1px solid #333;'> License</th>
					<th align='center' style='border:1px solid #333;'>File</th>
					 </tr>
					";
		
			if(isset($value['articleDetails'])){
				
				
				$subject = "OA Publishing Articles :".$publisher; 
				$type =  getValues($irts, "SELECT `value` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.type' AND parentRowID = '".$value['agreementRowID'] ."' AND `deleted` IS NULL", array('value'), 'singleValue');
					
				$startDate = getValues($irts, "SELECT `value` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.date.start' AND parentRowID = '".$value['agreementRowID'] ."' AND `deleted` IS NULL", array('value'), 'singleValue');
				
				$endDate = getValues($irts, "SELECT `value` FROM `metadata` WHERE SOURCE = 'pa' AND field = 'pa.date.end' AND parentRowID = '".$value['agreementRowID']."' AND `deleted` IS NULL", array('value'), 'singleValue');
			
			
				$message .= "<html>
								<body>
						
								<ul type='disc'> 
									 <li>Publisher:  ".ucwords($publisher)."</li>".
									"<li>Agreement Type:  ".$type."</li>".
									"<li>Eligible author(s): ".$eligibleauthors."</li>".
									"<li>OA Publishing agreement start date: ".$startDate."</li>".
									"<li>OA Publishing agreement end date: ".$endDate."</li>".
									"<li>Notification: True</li>
									</ul>
									<table width='400'>".$table.$value['articleDetails']."</table>";
						
				$message .="<br><br> Regards, \r\n  Yasmeen"."<br>On behalf of the KAUST Repository Team
				
						</body>
							</html>";
							
				// Always set content-type when sending HTML email
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

				//CC repository email
				$headers .= "From: " .IR_EMAIL. "\r\n";
				$headers .= "Cc: <".IR_EMAIL.">" . "\r\n";
				$test = mail($to,$subject,$message,$headers);
				
				
				$report .= '- Sent: '.$handle.PHP_EOL;
				$recordTypeCounts['sent']++;
				
			}	
				
			
		}
	
	}
	
	
		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	
}