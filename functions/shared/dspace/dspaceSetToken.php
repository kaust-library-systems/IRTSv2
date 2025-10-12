<?php
	function dspaceSetToken($headers)
	{
		foreach($headers as $header)
		{
			//set CSRF token in session
			if(strpos($header, 'DSPACE-XSRF-TOKEN:') !== FALSE)
			{
				//CSRF token will periodically change, token in session should be replaced whenever it changes
				$_SESSION['dspaceCsrfToken'] = trim(str_replace('DSPACE-XSRF-TOKEN: ', '', $header));

				//add CSRF token timestamp to the session
				$_SESSION['dspaceCsrfTokenTimestamp'] = time();
			}
		}
	}