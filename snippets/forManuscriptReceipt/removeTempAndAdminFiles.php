<?php
	//remove TEMP and ADMIN files
	foreach($bundleUUIDs as $bundleName => $bundleUUID)
	{
		if(in_array($bundleName, ['TEMP', 'ADMIN']))
		{
			$response = dspaceDeleteBundle($bundleUUID);
		
			//print_r($response);

			if($response['status'] == 'success')
			{
				$message .= '<br> --- '.$bundleName.' bundle with UUID '.$bundleUUID.' deleted.';
			}
			else
			{
				$message .= print_r($response, TRUE);

				$proceed = FALSE;

				break;
			}
		}
	}
