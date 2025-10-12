<?php
	//Define function to add book related info to Pure XML
	function bookInfo($journal, $isbn, $publisher)
	{
		global $purexml;
		
		if(!empty($journal))
		{
			if(!empty($isbn))
			{	
				$purexml .= '<!--Optional:-->
						<v1:printIsbns>
						<!--1 or more repetitions:-->';
				
				$isbns = explode('||', $isbn);
				foreach($isbns as $isbn)
				{
					$purexml .= '<v1:isbn>'.$isbn.'</v1:isbn>';
				}
				$purexml .= '</v1:printIsbns>';
			}
			
			if(!empty($journal))
			{
				$purexml .= '<v1:hostPublicationTitle>'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($journal))).'</v1:hostPublicationTitle>';	
			}			
			
			if(!empty($publisher))
			{	
				$purexml .= '
				<!--Optional:-->
				<v1:publisher>
					<v1:name>'.$publisher.'</v1:name>
				</v1:publisher>';
			}			
		}
	}
	
	//Define function to add conference proceedings related info to Pure XML
	function proceedingsInfo($journal, $confName, $confLocation, $confDate, $isbn, $publisher, $pubdate)
	{
		global $purexml;
		
		if(!empty($journal)||!empty($confName))
		{
			if(!empty($isbn))
			{	
				$purexml .= '<!--Optional:-->
						<v1:printIsbns>
						<!--1 or more repetitions:-->';
				
				$isbns = explode('||', $isbn);
				foreach($isbns as $isbn)
				{
					$purexml .= '<v1:isbn>'.$isbn.'</v1:isbn>';
				}
				$purexml .= '</v1:printIsbns>';
			}
			
			if(!empty($journal))
			{
				$purexml .= '<v1:hostPublicationTitle>'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($journal))).'</v1:hostPublicationTitle>';	
			}
			elseif(!empty($confName))
			{
				$purexml .= '<v1:hostPublicationTitle>'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($confName))).'</v1:hostPublicationTitle>';
			}								
			
			if(!empty($publisher))
			{	
				$purexml .= '
				<!--Optional:-->
				<v1:publisher>
					<v1:name>'.$publisher.'</v1:name>
				</v1:publisher>';
			}
			
			$purexml .= '<v1:event>';
			$purexml .= '<v1:type>conference</v1:type>';
			if(!empty($confName))
			{
				$purexml .= '<v1:title><commons:text lang="en" country="US">'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($confName))).'</commons:text></v1:title>';
			}
			elseif(!empty($journal))
			{
				$purexml .= '<v1:title><commons:text lang="en" country="US">'.str_replace('&', '&amp;', htmlspecialchars(strip_tags($journal))).'</commons:text></v1:title>';
			}
			if(!empty($confLocation))
			{
				$purexml .= '<v1:location>'.$confLocation.'</v1:location>';
			}
			if(!empty($confDate)&&strpos($confDate, ' to ')!==FALSE)
			{								
				$confDateParts = explode(' to ', $confDate);
				$startDate = date("d-m-Y", strtotime($confDateParts[0]));
				if($confDateParts[1]!='--')
				{
					$endDate = date("d-m-Y", strtotime($confDateParts[1]));
				}
				else
				{
					$endDate = '';
				}
				
				if(!empty($startDate))
				{
					$purexml .= '<v1:startDate>'.$startDate.'</v1:startDate>';
				}
				if(!empty($endDate))
				{
					$purexml .= '<v1:endDate>'.$endDate.'</v1:endDate>';
				}
			}
			else
			{
				$startDate = date("d-m-Y", strtotime($pubdate));
				if(!empty($startDate))
				{
					$purexml .= '<v1:startDate>'.$startDate.'</v1:startDate>';
				}
			}
			$purexml .= '</v1:event>';
		}
	}	
