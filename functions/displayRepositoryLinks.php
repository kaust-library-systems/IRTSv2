<?php
/*

**** This function adds links to the repository record to the message to be displayed in the review center

** Parameters :
	$itemID : the item ID in the repository
	$handle : the item handle in the repository

** Returns : 
	$message : the message to be displayed in the review center

*/

//--------------------------------------------------------------------------------------------

function displayRepositoryLinks($itemID, $handle)
{
	$message = '';

	$message .= 'Item ID: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'" target="_blank" rel="noopener noreferrer">'.$itemID.'</a><br>';
	$message .= 'Handle: <a href="'.REPOSITORY_URL.'/handle/'.$handle.'" target="_blank" rel="noopener noreferrer">'.$handle.'</a><br>';
	$message .= 'Full metadata page: <a href="'.REPOSITORY_URL.'/items/'.$itemID.'/full" target="_blank" rel="noopener noreferrer">'.$itemID.'</a><br><hr>';

	return $message;
}
