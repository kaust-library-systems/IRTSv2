<?php
	$record = getRecord($source, $idInSource, $template);

	$doi = '';
	if(isset($record['dc.identifier.doi'][0]))
	{
		$doi = $record['dc.identifier.doi'][0];
	}	
	
	$labels = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'irts' AND `idInSource` = '$idInIRTS' AND `field` = 'irts.harvest.basis' AND `deleted` IS NULL ORDER BY added DESC", array('value'));
	
	foreach($labels as $label)
	{
		echo "<span class='badge badge-pill badge-primary'>$label</span>";
	}

	echo listExistingRecords($record);
	
	$status = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'irts.status'), array('value'), 'singleValue');

	echo "<b>idInIRTS:</b> $idInIRTS --> <b>Source:</b> $source --> <b>idInSource:</b> $idInSource --> <b>Current Status:</b> $status";
	
	$processDate = getValues($irts, "SELECT added FROM `metadata` WHERE `source` = 'irts' AND `idInSource` = '$idInIRTS' AND `parentRowID` IS NULL AND `field` = 'irts.status' AND `deleted` IS NULL", array('added'), 'singleValue');
	
	if(!empty($processDate))
	{
		echo " --> <b>Date Set:</b> $processDate";
	}
	
	$note = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'irts' AND `idInSource` = '$idInIRTS' AND `field` = 'irts.note' AND `deleted` IS NULL", array('value'), 'singleValue');
	
	if(!empty($note))
	{
		echo " --> <b>Note:</b> $note";
	}
	
	$statusRowID = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'irts.status'), array('rowID'), 'singleValue');
	
	$processor = getValues($irts, "SELECT value FROM `metadata` WHERE `source` = 'irts' AND `idInSource` = '$idInIRTS' AND `field` = 'irts.processedBy' AND `deleted` IS NULL ORDER BY added DESC", array('value'), 'singleValue');
	
	if(!empty($processor))
	{
		echo " --> <b>Processed By:</b> $processor";
	}

	echo "<br><b>Information from $source: </b><br>";

	echo displayItemInfo($record, $template, 'initial', TRUE, []);
?>
