<?php
	//Define function to add abstract to Pure XML
	function commonElements($pubdate, $title, $abstract)
	{
		global $purexml;

		$pubdateparts = explode('-',$pubdate);
		$year = $pubdateparts[0];
		
		$date = '<v1:date>
					<commons:year>'.$year.'</commons:year>';
		if(!empty($pubdateparts[1]))
		{					
			$month = $pubdateparts[1];
			$date .= '<!--Optional:-->
				<commons:month>'.$month.'</commons:month>';
		}
		if(!empty($pubdateparts[2]))
		{					
			$day = $pubdateparts[2];
			$date .= '<!--Optional:-->
					<commons:day>'.$day.'</commons:day>';
		}
		$date .= '</v1:date>';	
		
		$purexml .= '<!--Optional:-->
		<v1:publicationCategory>research</v1:publicationCategory>
		<v1:publicationStatuses><v1:publicationStatus><v1:statusType>published</v1:statusType>'.$date.'</v1:publicationStatus></v1:publicationStatuses>
		<v1:workflow>approved</v1:workflow>
		<!--Optional:-->
		<v1:language>en_US</v1:language>
		<v1:title>
			<!--1 or more repetitions:-->
			<commons:text lang="en" country="US">'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($title), ENT_NOQUOTES)).'</commons:text>
		</v1:title>';
		
		$purexml .= '<!--Optional:-->
				<v1:abstract>
					<!--1 or more repetitions:-->
					<commons:text lang="en" country="US">'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($abstract), ENT_NOQUOTES)).'</commons:text>
				</v1:abstract>';
	}	
