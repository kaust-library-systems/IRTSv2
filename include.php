<?php
	$directoriesToInclude = array(
		"config/shared", 
		"config",
		"functions", 
		"functions/shared", 
		"functions/shared/dspace", 
		"functions/shared/powerAutomate", 
		"functions/forPureXML", 
		"functions/publisherAgreements", 
		//"functions/forRepositoryExports",  
		"functions/forDashboards",  
		"sources/arxiv", 
		"sources/crossref", 
		"sources/datacite", 
		"sources/doi", 
		"sources/dspace", 
		"sources/ebird", 
		"sources/europePMC", 
		"sources/github", 
		"sources/googleScholar", 
		"sources/googlePatents", 
		"sources/ieee", 
		"sources/lens",
		"sources/local", 
		"sources/orcid", 
		"sources/repository", 
		"sources/semanticScholar", 
		"sources/ncbi", 
		"sources/pure", 
		"sources/scienceDirect", 
		"sources/sherpa", 
		"sources/scopus", 
		"sources/unpaywall", 
		"sources/wos",
		"../sync-to-pure-via-api/functions");

	foreach($directoriesToInclude as $directory)
	{
		//load files
		$filesToInclude = array_diff(scandir(__DIR__.'/'.$directory), array('..', '.'));
		foreach($filesToInclude as $file)
		{
			if(strpos($file, '_template') == FALSE && is_file(__DIR__.'/'.$directory.'/'.$file))
			{
				include_once __DIR__.'/'.$directory.'/'.$file;
			}
		}
	}
	
	