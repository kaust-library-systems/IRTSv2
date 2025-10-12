<?php


/*

**** This function is responsible for standardizing use of dollar signs and representation of formulas in abstracts and titles.

** Parameters :
	$text: abstract or title. 
	$remove: boolean variable to remove the tags completely.

*/

//--------------------------------------------------------------------------------------------


function standardizeTheUseOfTags($text, $remove=FALSE)
{
	//remove unwanted content
	$unwantedTags = array('<jats:title>Abstract</jats:title>');
	foreach($unwantedTags  as $tag){
		$text = str_replace($tag,'',$text);
	}

	//Replace tags with the appropriate replacement	
	//tags arrays
	$tags = array(
				array('type'=>'subscript',
					'startTags'=>
						array('<jats:sub><jats:italic>', '<sub>', '<jats:sub>'),
					'replacementStartTag'=>'<sub>',
					'endTags'=> 
						array('</jats:italic></jats:sub>', '</jats:sub>'),
					'replacementEndTag'=>'</sub>'
					),
				array('type'=>'superscript',
					'startTags'=>
						array('<jats:sup>'),
					'replacementStartTag'=>'<sup>',
					'endTags'=> 
						array('</jats:sup>'),
					'replacementEndTag'=>'</sup>'
					),
				array('type'=>'italics',
					'startTags'=>
						array('<italic>', '<jats:italic>'),
					'replacementStartTag'=>'<i>',
					'endTags'=> 
						array('</italic>', '</jats:italic>'),
					'replacementEndTag'=>'</i>'
					),
				array('type'=>'line breaks',
					'startTags'=>
						array('<jats:p>', '<jats:sec>'),
					'replacementStartTag'=>'<br>',
					'endTags'=> 
						array('</jats:p>', '</jats:sec>'),
					'replacementEndTag'=>'<br>'
					),
				array('type'=>'remove',
					'startTags'=>
						array('<jats:title>'),
					'replacementStartTag'=>'',
					'endTags'=> 
						array('</jats:title>'),
					'replacementEndTag'=>''
					)
				);

	foreach ($tags as $tag)
	{
		// replace the tag
		if(!($remove))
		{	
			// replace each start tag with the correct starting tag
			foreach($tag['startTags'] as $startTag)
			{
				$text = str_replace($startTag, $tag['replacementStartTag'], $text);
			}

			// replace each end tag with the correct ending tag
			foreach($tag['endTags'] as $endTag)
			{
				$text = str_replace($endTag,  $tag['replacementEndTag'], $text);
			}
		}
		else
		{
			// remove all tags
			$text = strip_tags($text);
		}
	}
	
	// remove any leftover whitespace at the beginning or end
	$text = trim($text);

	return $text;
}