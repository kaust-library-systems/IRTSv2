<?php

/*

**** This snippet is responsible for displaying a form to add an identifier for a related publication to a dataset or software record.

*/

// check if a relatedPublicationDOI was entered in the form
if(isset($_POST['relatedPublicationDOI']) && preg_match('/10.\w*\/\w*/', $_POST['relatedPublicationDOI']))
{
	$relatedPublicationDOI = $_POST['relatedPublicationDOI'];
	
	$report = '<br>Add related publication DOI: '.$relatedPublicationDOI.'<br>';

	$existingRelatedPublicationDOIs = getValues($irts, "SELECT `place` FROM `metadata` 
										WHERE source = 'irts' 
										AND idInSource = '".$_POST['idInIRTS']."' 
										AND `field` = 'dc.related.publicationDOI' 
										AND deleted IS NULL", 
										array('value'), 'arrayOfValues');

	// check if the related publication DOI already exists in the record
	if(!in_array($relatedPublicationDOI, $existingRelatedPublicationDOIs))
	{
		// add the related publication DOI to the source record
		$result = saveValue('irts', $_POST['idInIRTS'], 'dc.related.publicationDOI', count($existingRelatedPublicationDOIs)+1, $relatedPublicationDOI, NULL);
		
		$report .= " - The related publication DOI has been added to the record.<br>";
	}
	else
	{
		$report .= " - The related publication DOI already exists in the record.<br>";
	}

	$url = 'reviewCenter.php?'.str_replace(' ', '+', $_POST['URL']).'&idInIRTS='.$idInIRTS;

	$report .= " - The related publication identifier has been added to the record. Click on the link to process the item: <a href='".$url."'>$idInIRTS</a><br>";

	echo $report;
}
// check if a relatedPublicationHandle was entered in the form
elseif(isset($_POST['relatedPublicationHandle']) && preg_match('/10754\/\w*/', $_POST['relatedPublicationHandle']))
{
	$relatedPublicationHandle = $_POST['relatedPublicationHandle'];
	
	$report = '<br>Add related publication Handle: '.$relatedPublicationHandle.'<br>';

	$existingRelatedPublicationHandles = getValues($irts, "SELECT `place` FROM `metadata` 
										WHERE source = 'irts' 
										AND idInSource = '".$_POST['idInIRTS']."' 
										AND `field` = 'dc.related.publicationHandle' 
										AND deleted IS NULL", 
										array('value'), 'arrayOfValues');

	// check if the related publication Handle already exists in the record
	if(!in_array($relatedPublicationHandle, $existingRelatedPublicationHandles))
	{
		// add the related publication Handle to the source record
		$result = saveValue('irts', $_POST['idInIRTS'], 'dc.related.publicationHandle', count($existingRelatedPublicationHandles)+1, $relatedPublicationHandle, NULL);
		
		$report .= " - The related publication Handle has been added to the record.<br>";
	}
	else
	{
		$report .= " - The related publication Handle already exists in the record.<br>";
	}

	$url = 'reviewCenter.php?'.str_replace(' ', '+', $_POST['URL']).'&idInIRTS='.$idInIRTS;

	$report .= " - The related publication identifier has been added to the record. Click on the link to process the item: <a href='".$url."'>$idInIRTS</a><br>";

	echo $report;
}
else
{
	//display the form
	echo '
	<div class="col-sm-12 alert-warning border border-dark rounded">
	<b> Add a related publication identifier to the IRTS record with idInIRTS:'.$idInIRTS.'</b>
	</div>
	
	<br>
	
	<div class="col-lg-6">
	<form action="reviewCenter.php?'.$selections.'" method="POST" enctype="multipart/form-data" autocomplete="off">
		<div class="form-group">
			<label for="doi">Related Publication DOI:</label>
			<textarea placeholder="10.xxxxx/xxxxxxx" class="form-control" rows="1" name="relatedPublicationDOI"></textarea>
		</div>
		<div class="form-group">
			<label for="handle">Related Publication Handle:</label>
			<textarea placeholder="10754/xxxxxx" class="form-control" rows="1" name="relatedPublicationHandle"></textarea>
		</div>
		<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
		<input type="hidden" name="URL" value="'.$selections.'">
		<button class="btn btn-primary" type="submit" name="action" value="addRelatedPublication">Add Related Publication Identifier To The Record</button>
	</form>
	</div>
	';
}
