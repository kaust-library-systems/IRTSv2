<?php

/*

**** This snippet displays a form to change the type and template of the record.

*/

// check if a newType was entered in the form
if(isset($_POST['newType']) && !empty($_POST['newType']))
{
	$newType = $_POST['newType'];
	
	$report = '<br>Change type to: '.$newType.'<br>';

	// update the type in the source record
	$result = saveValue('irts', $_POST['idInIRTS'], 'dc.type', 1, $newType, NULL);

	//reset selections as empty array
	$selections = array();

	// update the itemType selection to the new type
	foreach($_SESSION['selections'] as $selection=>$value)
	{
		if($selection == 'itemType')
		{
			// update the itemType selection
			$selections[] = $selection.'='.$newType;
		}
		else
		{
			// keep other selections unchanged
			$selections[] = $selection.'='.$value;
		}
	}
	$selections = implode('&', $selections);

	$url = 'reviewCenter.php?'.$selections.'&idInIRTS='.$_POST['idInIRTS'];

	$report .= " - Click on the link to process the item in the new type template: <a href='".$url."'>".$_POST['idInIRTS']."</a><br>";

	echo $report;
}
else
{
	//display the form
	echo '
	<div class="col-sm-12 alert-warning border border-dark rounded">
	<b> Change the type and template for the IRTS record with idInIRTS: '.$idInIRTS.'</b>
	</div>
	
	<br>
	
	<div class="col-lg-6">
	<form action="reviewCenter.php?'.$selections.'" method="POST" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">';
	
	// Display a dropdown to select the new type for the record
	echo '
		<div class="form-group">
			<label for="newType">Select new type for the record: </label>
			<select name="newType" class="form-control">';

	//get list of available templates
	$templateNames = getValues(
		$irts, 
		"SELECT DISTINCT `idInSource` FROM `metadata` 
			WHERE `source` = 'irts' 
			AND `idInSource` LIKE 'itemType_%'", 
		array('idInSource'),
		'arrayOfValues');

	//loop through the types and display them as options
	foreach($templateNames as $type)
	{
		$type = str_replace('itemType_', '', $type);
		
		echo '<option value="'.$type.'">'.$type.'</option>';
	}

	echo '</select>
		</div>';
	
	echo '
		<button class="btn btn-primary" type="submit" name="action" value="changeTypeAndTemplate">Change Type and Template</button>
	</form>
	</div>
	';
}
