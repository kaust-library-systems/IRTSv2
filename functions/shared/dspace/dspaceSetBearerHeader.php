<?php
	function dspaceSetBearerHeader($headers)
	{
		foreach($headers as $header)
		{
			if(strpos($header, 'Authorization: Bearer ') !== FALSE)
			{
				$_SESSION['dspaceBearerHeader'] = trim($header);

				//add bearer token timestamp to the session
				$_SESSION['dspaceBearerHeaderTimestamp'] = time();
			}
		}
	}