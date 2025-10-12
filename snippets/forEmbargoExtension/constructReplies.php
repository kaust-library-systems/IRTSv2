<?php
	//reply on successful extension
	$replyOnSuccess = 'Dear '.$senderName.',

	Thank you. The embargo has been extended until {newEmbargoEndDatePlaceholder}.
		
	Let me know if you have any questions. 

	Regards, 
	'.$_SESSION['displayname'].'
	on behalf of The University Library Repository Team';
	
	//remove tabs from displaying in email
	$replyOnSuccess = preg_replace('/\t+/', '', $replyOnSuccess);