<?php
	//reply for when other version is needed.
	$requestForOtherVersion = 'Dear '.$senderName.',

	The file you sent appears to be the final, publisher-formatted PDF from the journal website. The policy of this journal only allows the accepted version (not the publisher’s final version) to be placed in the repository. This is defined as the author’s manuscript with any changes made as a result of the peer-review process, but prior to publisher’s final copy-editing or formatting. If you or your co-authors have saved such a version of the article, please send it to us.

	Let me know if you have any questions. 

	Regards, 
	';
	
	$requestForOtherVersion .= $_SESSION['displayname'];
	
	$requestForOtherVersion .= '
	on behalf of The University Library Repository Team';
	
	//remove tabs from displaying in email
	$requestForOtherVersion = preg_replace('/\t+/', '', $requestForOtherVersion);

	//reply on successful upload
	$replyOnSuccess = 'Dear '.$senderName.',

	Thank you. It has been added to the repository at: '.REPOSITORY_URL.'/handle/'.$handle.'.';

	if(!empty($embargoEndDate))
	{
		$replyOnSuccess .= '
		
		Based on the publisher policy an embargo has been set on public access to the files. The public will be able to access the files after expiration of the embargo on '.$embargoEndDate.'.';
	}
	
	$replyOnSuccess .= '
		
		Let me know if you have any questions. 

		Regards, 
		'.$_SESSION['displayname'];
	
	$replyOnSuccess .= '
	on behalf of The University Library Repository Team';
	
	//remove tabs from displaying in email
	$replyOnSuccess = preg_replace('/\t+/', '', $replyOnSuccess);