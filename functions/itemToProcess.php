<?php
	//define function to show full item info for processing
	function itemToProcess($formType, $template, $idInIRTS)
	{
		global $irts, $page;

		//Set startTime for this item
		$_SESSION["variables"]["startTime"]=date("Y-m-d H:i:s");

		foreach($_SESSION['selections'] as $selection=>$value)
		{
			$selections[]=$selection.'='.$value;
		}
		if(isset($page))
		{
			$selections[]='page='.$page;
		}
		$selections = implode('&', $selections);

		if($formType === 'processNew')
		{
			$source = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'irts.source'), array('value'), 'singleValue');
			
			$idInSource = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, TRUE, 'irts.idInSource'), array('value'), 'singleValue');
		}
		elseif(in_array($formType, array('editExisting','review')))
		{
			$source = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, NULL, 'irts.source'), array('value'), 'singleValue');
			
			if($source === 'dspace')
			{
				$idInSource = getValues($irts, setSourceMetadataQuery('irts', $idInIRTS, TRUE, 'irts.idInSource'), array('value'), 'singleValue');
			}
			else
			{
				$source = 'irts';
				$idInSource = $idInIRTS;
			}
		}

		echo '<div class="container">';
		include 'snippets/displayItemDetails.php';
		include 'snippets/html/processButtons.php';
		echo '</div>';
	}
