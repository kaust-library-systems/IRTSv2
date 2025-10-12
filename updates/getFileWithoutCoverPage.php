<?php
function getFileWithoutCoverPage($report, $errors, $recordTypeCounts)
{
	//Get initial CSRF token and set in session
	$response = dspaceGetStatus();
				
	//Log in
	$response = dspaceLogin();

	if($response['status'] == 'success')
	{
		$response = dspaceGetShortLivedToken();

		if($response['status'] == 'success')
		{
			$json = $response['body'];

			$shortLivedToken = json_decode($json, TRUE)['token'];

			$response = dspaceGetBitstreamsContent('cdbda7f2-200e-4a06-bd66-0ae5289a4caa');
			//$response = dspaceGetBitstreamsContent('cdbda7f2-200e-4a06-bd66-0ae5289a4caa', $shortLivedToken);

			if($response['status'] == 'success')
			{
				$bitstream = $response['body'];

				foreach($response['headers'] as $header)
				{
					//get file name from header
					if(strpos($header, 'Content-Disposition:') !== FALSE)
					{
						$fileName = 'With Cover Page-'.trim(str_replace('"', '', str_replace('Content-Disposition: attachment;filename="', '', $header)));

						echo $fileName.PHP_EOL;
					}
				}
				
				$filePath = UPLOAD_FILE_PATH.$fileName;

				$file = fopen($filePath, 'w');
				fwrite($file, $bitstream);
				fclose($file);

				$report .= 'File saved to '.$filePath.PHP_EOL;
			}
			else
			{
				$errors[] = 'Error getting bitstream content: '.$response['response'];
			}
		}
	}

	$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

	return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
}