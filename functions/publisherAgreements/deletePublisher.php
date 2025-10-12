<?php
/*

**** This file is responsible deleting a Pulisher.

** Parameters :
	$publisherID: Unique identifier for each publisher.

** Output : returns a HTML page.

*/

//------------------------------------------------------------------------------------------------------------

function deletePublisher($publisherID){
		
	global $irts;
		
	// mark all the record as deleted
	//$irts->query("UPDATE `metadata` SET `deleted`='".date("Y-m-d H:i:s")."' WHERE `idInSource`='$publisherID' and `source`='pa' and `deleted` IS NULL");
	
	update($irts, 'metadata', array("deleted"), array(date("Y-m-d H:i:s"), $publisherID), 'idInSource');
		
	header("Location: PA.php");
	die();
}