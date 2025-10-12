<?php
	$reviewCenterLandingPageLoadTime = microtime(true);
	
	echo '<div class="container">';

	echo '<div class="row">';
	
	//start left side box
	echo '<div class="col border border-dark rounded m-2 p-4">';

	//link to merge duplicates form
	echo '<a href="reviewCenter.php?formType=mergeRecords" type="button" class="btn btn-primary rounded">Merge Records</a>';

	//link to update embargo form
	echo '<a href="reviewCenter.php?formType=updateEmbargo" type="button" class="btn btn-primary rounded" style="margin-left: 10px;">Update Embargo</a>';
	
	echo '<h4><b>Action Needed:</b></h4>';

	//start action needed table
	echo '<table><tr><th>Type</th><th>Count</th></tr>';

	// check for any embargo extension requests awaiting approval
	$response = dspaceSearch('query=kaust.embargo.extensionRequested:*&size=1');

	//if search successful
	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		$total = $results['_embedded']['searchResult']['page']['totalElements'];

		echo '<tr><td>Embargo Extension Requests Received</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=approveEmbargoExtension">'.$total.'</a></td></tr>';
	}
	else
	{
		echo '<tr><td>Search Error: </td><td><details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}

	// check for any thesis submissions awaiting approval
	$response = dspaceSearch('configuration=workflow&scope='.ETD_COMMUNITY_UUID.'&size=1');

	//if search successful
	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		$total = $results['_embedded']['searchResult']['page']['totalElements'];

		echo '<tr><td>Thesis and Dissertation Submissions Awaiting Approval</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkThesisSubmission">'.$total.'</a></td></tr>';
	}
	else
	{
		echo '<tr><td>Search Error: Thesis and Dissertation Submissions Awaiting Approval</td><td><details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}

	// check for any direct submissions in the research community awaiting approval
	$response = dspaceSearch('configuration=workflow&scope='.RESEARCH_COMMUNITY_UUID.'&size=1');
    //if search successful
	if($response['status'] == 'success')
	{
	  $results = json_decode($response['body'], TRUE);
	  $total = $results['_embedded']['searchResult']['page']['totalElements'];
	  echo '<tr><td>Direct Submissions Awaiting Approval</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkDirectSubmission">'.$total.'</a></td></tr>';
	}
	else
	{
		echo '<tr><td>Search Error: Direct Submissions Awaiting Approval</td><td><details>
		<summary>Details</summary>
		<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}

	//end action needed table
	echo '</table>';

	//line between sections
	echo '<hr>';

	echo '<h4><b>Old Records Needing Review:</b></h4>';

	//check for records that are marked with a note for review
	$problemsToCheck = getValues(
		$irts, 
		"SELECT `value` AS `problemType`, COUNT(`idInSource`) AS `problemCount` FROM `metadata` 
			WHERE `source` LIKE 'irts' 
			AND `field` LIKE 'irts.note' 
			AND `deleted` IS NULL
			GROUP BY `problemType`",
		array('problemType', 'problemCount')
	);

	foreach($problemsToCheck as $problemToCheck)
	{
		//if the problemType is in the list of problemTypes
		if(array_key_exists($problemToCheck['problemType'], $problemTypes))
		{
			$problemType = $problemToCheck['problemType'];

			$problemDescription = $problemTypes[$problemType]['description'];
			
			//display problem type above list of types
			echo '<tr><td colspan="2"><b>'.$problemDescription.'</b></td></tr>';

			//start old records needing review table
			echo '<table><tr><th>Type</th><th>Count</th></tr>';

			//get counts of itemTypes for each problemType
			$itemTypes = getValues(
				$irts, 
				"SELECT `value` AS `itemType`, COUNT(`idInSource`) AS `itemCount` FROM `metadata` 
					WHERE `source` LIKE 'irts' 
					AND `field` LIKE 'dc.type' 
					AND `deleted` IS NULL
					AND `idInSource` IN (
						SELECT `idInSource` FROM `metadata` 
						WHERE `source` LIKE 'irts' 
						AND `field` LIKE 'irts.note' 
						AND `deleted` IS NULL
						AND `value` = '$problemType'
					)
					GROUP BY `itemType`",
				array('itemType', 'itemCount')
			);

			foreach($itemTypes as $itemType)
			{
				echo '<tr><td style="padding-left: 20px;">'.$itemType['itemType'].'</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=review&problemType='.urlencode($problemToCheck['problemType']).'&itemType='.urlencode($itemType['itemType']).'">'.$itemType['itemCount'].'</a></td></tr>';
			}
		}
	}
	
	//end old records needing review table
	echo '</table></div>';

	//start right side box
	echo '<div class="col border border-dark rounded m-2 p-4">';

	echo '<a href="reviewCenter.php?formType=addNewItem" type="button" class="btn btn-primary rounded">Add New Item</a>';
	
	echo '<a href="reviewCenter.php?formType=editExistingItem" type="button" class="btn btn-primary rounded" style="margin-left: 10px;">Edit Existing Item</a>';

	echo '<a href="reviewCenter.php?formType=uploadFile" type="button" class="btn btn-primary rounded" style="margin-left: 10px;">Upload a File</a>';
	
	echo '<h4><b>Action Needed:</b></h4>';

	//start action needed table
	echo '<table><tr><th>Type</th><th>Count</th></tr>';

	//check for possible duplicate pairs needing review
	$countOfPossibleDuplicatePairs = getValues(
		$irts, 
		"SELECT COUNT(DISTINCT idInSource) AS count FROM `metadata` 
			WHERE `source` LIKE 'irts' 
			AND `field` LIKE 'irts.duplicate.status' 
			AND `value` LIKE 'Possible Duplicates to Check' 
			AND `deleted` IS NULL", 
		array('count'), 
		'singleValue'
	);

	echo '<tr><td>Possible Duplicate Pairs</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkPossibleDuplicates">'.$countOfPossibleDuplicatePairs.'</a></td></tr>';
	
	// check for any emails received in response to accepted manuscript requests
	$response = dspaceSearch('query=kaust.manuscript.received:*&size=1');

	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		$total = $results['_embedded']['searchResult']['page']['totalElements'];

		echo '<tr><td>Emails Received in Response to Accepted Manuscript Requests</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkReceivedFiles">'.$total.'</a></td></tr>';
	}
	else
	{
		echo '<tr><td>Search Error: </td><td><details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}

	// check for any items with missing files (anonymous search to only find public records)
	$response = dspaceSearch('query=(dc.rights.uri:creativecommons.org%20OR%20dc.identifier.arxivid:*)%20AND%20(dc.type:Article%20OR%20dc.type:%22Book%20Chapter%22%20OR%20dc.type:%22Conference%20Paper%22%20OR%20dc.type:Preprint)%20AND%20NOT%20arxiv.status:withdrawn&f.has_content_in_original_bundle=false,equals&scope='.RESEARCH_COMMUNITY_UUID.'&size=1', TRUE);

	if($response['status'] == 'success')
	{
		$results = json_decode($response['body'], TRUE);

		$total = $results['_embedded']['searchResult']['page']['totalElements'];

		echo '<tr><td>Items with Missing Files</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=checkMissingFiles">'.$total.'</a></td></tr>';
	}
	else
	{
		echo '<tr><td>Search Error: </td><td><details>
			<summary>Details</summary>
			<p>'.print_r($response, TRUE).'</p>
		</details></td></tr>';
	}

	//end action needed table
	echo '</table>';

	//line between sections
	echo '<hr>';

	echo '<h4><b>Current Priority Items to Process:</b></h4>';

	//start priority items table
	echo '<table><tr><th>Type</th><th>Count</th></tr>';

	$newItemTypes = getValues($irts, "SELECT COUNT(*) AS `typeCount`, type.`value` itemType 
		FROM `metadata` `type` 
		LEFT JOIN metadata `status` USING(idInSource)
		WHERE type.`source` LIKE 'irts' 
		AND type.`field` LIKE 'dc.type' 
		AND type.value IN ('Article','Book Chapter','Conference Paper','Dataset','Preprint','Presentation')
		AND status.field LIKE 'irts.status' 
		AND status.value LIKE 'inProcess' 
		AND status.deleted IS NULL 
		GROUP BY type.`value` 
		ORDER BY `typeCount` DESC", array('typeCount', 'itemType'));

	foreach($newItemTypes as $newItemType)
	{
		echo '<tr><td>'.$newItemType['itemType'].'</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=processNew&itemType='.str_replace(' ', '+',$newItemType['itemType']).'&harvestBasis=priority&page=0">'.$newItemType['typeCount'].'</a></td></tr>';
	}

	echo '</table><hr><h4><b>All new items to process by type:</b></h4><table><tr><th>Type</th><th>Count</th></tr>';

	if(isset($_GET['showAllNewItems']))
	{
		$newItemTypes = getValues($irts, "SELECT COUNT(*) AS `typeCount`, type.`value` itemType 
			FROM `metadata` type LEFT JOIN metadata status USING(idInSource)
			WHERE type.`source` LIKE 'irts' 
			AND type.`field` LIKE 'dc.type' 
			AND status.field LIKE 'irts.status' 
			AND status.value LIKE 'inProcess' 
			AND status.deleted IS NULL 
			GROUP BY type.`value` 
			ORDER BY `typeCount` DESC", array('typeCount', 'itemType'));

		foreach($newItemTypes as $newItemType)
		{
			echo '<tr><td>'.$newItemType['itemType'].'</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=processNew&itemType='.str_replace(' ', '+',$newItemType['itemType']).'&harvestBasis=all&page=0">'.$newItemType['typeCount'].'</a></td></tr>';
		}
	}
	else
	{
		echo '<tr><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?showAllNewItems=yes">Show All New Items (will take time to load)</a></td></tr>';
	}
	
	echo '</table></div></div><hr>';

	echo '<div class="row"><div class="col border border-dark rounded m-2 p-4"><h4><b>Unmatched variants:</b></h4><table><tr><th>Type</th><th>Count</th></tr>';

	if(isset($_GET['showUnmatchedVariants']))
	{	
		$unmatchedVariantTypes[0]['itemType'] = 'Org Unit Name';

		$unmatchedVariantTypes[0]['typeCount'] = getValues($irts, "SELECT COUNT(DISTINCT value) AS count FROM `metadata` WHERE `field` = 'kaust.acknowledged.supportUnit' AND value NOT IN (SELECT value FROM metadata WHERE source IN ('irts','local') AND field IN ('local.org.name','local.name.variant','irts.unmatched.orgUnitName'))", array('count'), 'singleValue');

		$unmatchedVariantTypes[1]['itemType'] = 'Affiliation';

		$unmatchedVariantTypes[1]['typeCount'] = getValues($irts, "SELECT COUNT(DISTINCT value) AS count FROM `metadata` WHERE `field` = 'irts.unmatched.affiliation' AND value NOT IN (SELECT value FROM metadata WHERE source = 'local' AND (field = 'local.org.name' OR field = 'local.name.variant') AND deleted IS NULL)", array('count'), 'singleValue');

		$unmatchedVariantTypes[2]['itemType'] = 'Person Name';

		$unmatchedVariantTypes[2]['typeCount'] = getValues($irts, "SELECT COUNT(DISTINCT value) AS count FROM `metadata` WHERE `field` = 'irts.unmatched.person' AND value NOT IN (SELECT value FROM metadata WHERE source = 'local' AND field = 'local.name.variant' AND deleted IS NULL)", array('count'), 'singleValue');

		foreach($unmatchedVariantTypes as $unmatchedVariantType)
		{
			echo '<tr><td>'.$unmatchedVariantType['itemType'].'</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=variantMatching&itemType='.$unmatchedVariantType['itemType'].'">'.$unmatchedVariantType['typeCount'].'</a></td></tr>';
		}
	}
	else
	{
		echo '<tr><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?showUnmatchedVariants=yes">Show Unmatched Variants (will take time to load)</a></td></tr>';
	}	

	echo '</table></div>';

	echo '<div class="col border border-dark rounded m-2 p-4"><h4><b>Steps needing review:</b></h4><table><tr><th>Type</th><th>Count</th></tr>';
	
/* 	$stepsToReview[0]['itemType'] = 'Acknowledgements';

	$stepsToReview[0]['typeCount'] = getValues($irts, "SELECT COUNT(DISTINCT i.idInSource) AS count
		FROM `metadata` m
		LEFT JOIN metadata m2 USING(idInSource)
		LEFT JOIN metadata i ON i.value = m2.value
		WHERE m.`source` LIKE 'repository'
		AND m.`field` LIKE 'dc.description.sponsorship'
		AND m.deleted IS NULL
		AND m2.`source` LIKE 'repository'
		AND m2.`field` LIKE 'dc.identifier.doi'
		AND m2.deleted IS NULL
		AND i.`source` LIKE 'irts'
		AND i.`field` LIKE 'dc.identifier.doi'
		AND i.deleted IS NULL
		AND i.idInSource NOT IN (
		SELECT `idInSource` FROM metadata WHERE field IN ('kaust.acknowledged.person', 'kaust.acknowledged.supportUnit', 'kaust.grant.number', 'kaust.acknowledgement.type', 'local.acknowledgement.type'))
		AND m.idInSource NOT IN (
		SELECT `idInSource` FROM metadata WHERE field IN ('kaust.acknowledged.supportUnit', 'kaust.grant.number', 'kaust.acknowledgement.type', 'local.acknowledgement.type'))
		AND i.idInSource IN (
		SELECT DISTINCT idInSource FROM `metadata` WHERE `field` = 'irts.check.acknowledgement' AND value = 'yes' AND deleted IS NULL)", array('count'), 'singleValue');

	$stepsToReview[1]['itemType'] = 'Dataset Relationships';

	$stepsToReview[1]['typeCount'] = getValues($irts, "SELECT COUNT(DISTINCT idInSource) as count 
		FROM `metadata`
		WHERE source = 'irts'
		AND field IN ('dc.related.accessionNumber','dc.related.datasetDOI','dc.related.datasetURL','dc.related.codeURL') 
		AND deleted IS NULL
		AND (
			idInSource IN (
				SELECT idInSource FROM metadata WHERE source = 'irts'
				AND field = 'dc.identifier.doi'
				AND value IN (
					SELECT value FROM metadata WHERE source = 'repository'
					AND field = 'dc.identifier.doi'
					AND deleted IS NULL
				)
				AND deleted IS NULL
			)
			OR
			idInSource IN (
				SELECT idInSource FROM metadata WHERE source = 'irts'
				AND field = 'dc.identifier.arxivid'
				AND value IN (
					SELECT value FROM metadata WHERE source = 'repository'
					AND field = 'dc.identifier.arxivid'
					AND deleted IS NULL
				)
				AND deleted IS NULL
			)
		)", array('count'), 'singleValue');

	foreach($stepsToReview as $stepToReview)
	{
		echo '<tr><td>'.$stepToReview['itemType'].'</td><td><a type="button" class="btn btn-primary rounded" href="reviewCenter.php?formType=reviewStep&itemType='.$stepToReview['itemType'].'">'.$stepToReview['typeCount'].'</a></td></tr>';
	} */

	echo '</table></div>';

	echo '</div><hr></div>';
	
	$reviewCenterLandingPageLoadTime = microtime(true)-$reviewCenterLandingPageLoadTime;

	insert($irts, 'messages', array('process', 'type', 'message'), array('reviewCenterLandingPageLoadTime', 'report', $reviewCenterLandingPageLoadTime.' seconds'));
?>
