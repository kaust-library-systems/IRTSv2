<?php 

    use RenanBr\BibTexParser\Listener;
	use RenanBr\BibTexParser\Parser;
	use RenanBr\BibTexParser\Processor;
	require 'vendor/autoload.php';

function generateHTML($bitstreamID)
    {
		global $irts; 
		
		$token = loginToDSpaceRESTAPI();
	
		
		 $bibtex = '%%% Placeholder comment %%%'.PHP_EOL.getBitstreamFromDSpaceRESTAPI($bitstreamID, $token, '/retrieve');
	
	    // Create and configure a Listener
	    $listener = new Listener();
	    $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
	    $listener->addProcessor(new Processor\NamesProcessor());
	    $listener->addProcessor(new Processor\KeywordsProcessor());
	    $listener->addProcessor(new Processor\DateProcessor());
	    // $listener->addProcessor(new Processor\FillMissingProcessor([/* ... */]));
	    $listener->addProcessor(new Processor\TrimProcessor());
	    // $listener->addProcessor(new Processor\UrlFromDoiProcessor());
	    // $listener->addProcessor(new Processor\LatexToUnicodeProcessor());
	    // ... you can append as many Processors as you want
	    // Create a Parser and attach the listener
	    $parser = new Parser();
	    $parser->addListener($listener);
								
	    // Parse the content, then read processed data from the Listener
	    $parser->parseString($bibtex); 
	    // or parseFile('/path/to/file.bib')
	    $entries = $listener->export();
						
	    $html = '<details>
	    <summary>References (click to expand)</summary>
	  
	    <br>
	    <div>Click <a href="https://'.REPOSITORY_BASE_URL.'/rest/bitstreams/'.$bitstreamID.'/retrieve">here</a> to download the full reference list in bibtex format.</div>
	    <br>
	  
	    <ol>'.PHP_EOL;
		
		//$html = utf8_encode($html);
		 
		 foreach($entries as $entry)
	    {
	      $html .="<li>";
		    $citation = $entry['title'];
									
		    if(!empty($entry['journal']))
		    {
			   $citation .= ', <i>'.$entry['journal'].'</i>';
		    }
		    elseif(!empty($entry['booktitle']))
		    {
		       $citation .= ', <i>'.$entry['booktitle'].'</i>';
		    }
		    elseif(!empty($entry['school']))
		    {
		       $citation .= ', '.$entry['school'].' ['.$entry['type'].']';
		    }
		    elseif(!empty($entry['type']))
		    {
	          $citation .= ' ['.$entry['type'].']';
		    }
		    if(!empty($entry['year']))
		    {
		       $citation .= ' ('.$entry['year'].')';
		    }
		   if(!empty($entry['author']))
		    {
		      $authors = [];
			  foreach($entry['author'] as $author)
			    {
			      $authors[] = $author['first'].' '.$author['last'];
			    }
			  $citation .= '<br>'.implode(', ', $authors);
			}
			if(!empty($entry['doi']))
			{
	          $citation .= '<br>[<a href="https://doi.org/'.$entry['doi'].'">DOI</a>]';
			}
		    elseif(!empty($entry['url']))
		    {
	            $citation .= '<br>[<a href="'.$entry['url'].'">link</a>]';
		    }
		   $html .= $citation;
		   $html .="</li>".PHP_EOL;
		}
		$html .='
		</ol>
		</details>';
		
		$unwanted_array = array('{\"o}'=>'ö' , '{\"u}'=>'ü','{\"{u}}'=>'ü' , '{\"a}'=>'ä', "{\'e}"=>'é',"\'e"=>'é', "\'{e}"=>'é',"\'{i}"=>'í',"{\'a}"=>'á','{'=>'' , '}'=>''); 
		$html = strtr( $html , $unwanted_array );
		
		return $html;

	}
	
	