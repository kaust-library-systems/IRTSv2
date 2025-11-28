<?php
	//constants shared with IOI and DOIMinter are in config/shared/constants.php

	//Pure AWS API
	//define('PURE_API_URL', 'https://pure.kaust.edu.sa/ws/api/'); //production
	define('PURE_API_URL', 'https://kaust-staging.elsevierpure.com/ws/api/'); //staging

	//Research community UUID
	//On test server
	define('RESEARCH_COMMUNITY_UUID', '951afedc-2381-4ad5-9ee9-d03b033dc9fd');

	//On production server
	//define('RESEARCH_COMMUNITY_UUID', 'c6099bae-524f-4975-8295-611697138c6d');
	
	define('ACKNOWLEDGEMENT_ONLY_COLLECTION_ID', '523d43cf-8f7d-4fa5-8024-c588c467ceac');
		
	define('ANONYMOUS_GROUP_ID','59d29420-b3db-4ba5-81f4-4ea925d4774e');
	
	define('CurrentPosterSession_community_UUID','f9e3cbd3-4a48-4b86-9d7c-5609dbb1a156');

	define('EventsCommunityUUID','01e2ddc3-0f03-4905-91fb-c3be5fbb28c6');

	//List of registrar team members to receive ETD notices, set to IR_EMAIL for testing
	define('REGISTRAR_EMAILS', IR_EMAIL);
	//define('REGISTRAR_EMAILS', 'RegistrarHelpDesk@KAUST.EDU.SA,fayez.albarakati@kaust.edu.sa,samar.alluhaybi@kaust.edu.sa,kamaraj.sindhu@kaust.edu.sa');

	//List of scientific illustrators to receive acknowledgement notices, set to IR_EMAIL for testing
	define('ILLUSTRATORS', array("Xavier Pita"=>"xavier.pita@kaust.edu.sa","Ivan Gromicho"=>"ivan.gromicho@kaust.edu.sa", "Heno Hwang"=>"heno.hwang@kaust.edu.sa"));

	//Name and email of library staff member responsible for tracking OA publishing agreements, set to IR_EMAIL for testing
	define('PUBLISHER_AGREEMENT_NOTICE_RECIPIENT', array('name'=>'Nevena','email'=>'nevena.tomic@kaust.edu.sa'));
	
	define('OAPOLICY_URL', 'http://library.kaust.edu.sa/OpenAccessPolicy');

	define('OAPOLICY_START_DATE', '2014-07');
	
	define('PUBLICATION_TRACKING_START_DATE', '2009-01-01');
	
	define('ORCID_API_URL', 'https://pub.orcid.org/v3.0/');
	
	// Comparable publication sources (which primarily track items with Crossref DOIs, such as journal articles and conference papers)
	define('PUBLICATION_SOURCES', array('crossref','europePMC','googleScholar','ieee','repository','scopus','wos'));
	
	// Item types to be considered in charts showing "Publications"
	define('PUBLICATION_TYPES', array('Article', 'Book', 'Book Chapter', 'Conference Paper'));
	
	//Metadata Source URLs
	define('DATACITE_API', 'https://api.datacite.org/');
	
	define('DATACITE_DATA', 'https://data.datacite.org/');

	define('SEMANTIC_SCHOLAR_API', 'https://api.semanticscholar.org/graph/v1/');
	
	define('ARXIV_API_URL', 'http://export.arxiv.org/api/query?search_query=');
	
	define('CROSSREF_API', 'https://api.crossref.org/');
	
	define('ELSEVIER_API_URL', 'https://api.elsevier.com/content/');
	
	define('EUROPEPMC_API_URL', 'https://www.ebi.ac.uk/europepmc/webservices/rest/');
	
	define('GOOGLE_SCHOLAR_URL', 'https://scholar.google.com/scholar');
	
	define('IEEE_API', 'http://ieeexploreapi.ieee.org/api/v1/search/articles?');

	//Documentation at https://developer.clarivate.com/apis/wos-starter
	define('WOS_API_URL', 'https://api.clarivate.com/apis/wos-starter/v1/');
	
	//Documentation at https://github.com/adsabs/adsabs-dev-api
	define('ADSABS_API_URL', 'https://api.adsabs.harvard.edu/v1/search/query?');
	
	define('SHERPA_ROMEO_API_URL', 'https://v2.sherpa.ac.uk/cgi/retrieve?');
	
	define('UNPAYWALL_API_URL', 'https://api.unpaywall.org/v2/');

	//Patent Link Base URLs
	define('USPTO_GRANTED_URL', 'http://patft.uspto.gov/netacgi/nph-Parser?patentnumber=');
	
	//With variables inserted to replace ##applicationNumber##
	define('USPTO_APPLICATION_URL', 'http://appft.uspto.gov/netacgi/nph-Parser?Sect1=PTO1&Sect2=HITOFF&d=PG01&p=1&u=%2Fnetahtml%2FPTO%2Fsrchnum.html&r=1&f=G&l=50&s1=%22##applicationNumber##%22.PGNR.&OS=DN/##applicationNumber##&RS=DN/##applicationNumber##');		
	
	define('USPTO_ASSIGNMENTS_URL', 'http://assignment.uspto.gov/#/search?adv=patNum:');
	
	define('USPTO_APPLICATION_ASSIGNMENTS_URL', 'http://assignment.uspto.gov/#/search?adv=publNum:');
	
	define('GOOGLE_PATENTS_URL', 'https://patents.google.com/');

	define('ESPACENET_URL', 'http://worldwide.espacenet.com/publicationDetails/biblio?');

	//Patent PDF Source URLs
	define('USPTO_PDF_URL', 'http://pimg-fpiw.uspto.gov/fdd/');
	
	define('USPTO_APPLICATION_PDF_URL', 'http://pimg-faiw.uspto.gov/fdd/');
	
	define('FPO_PDF_URL', 'http://www.freepatentsonline.com/');
	
	define('GOOGLEPATENTS_PDF_URL', 'http://patentimages.storage.googleapis.com/pdfs/');
	
	//Fixed institutional name strings for querying particular services
	define('GOOGLE_ASSIGNEE_NAME', 'King Abdullah University Of Science And Technology');
	
	define('SCOPUS_AF_ID', '"King Abdullah University of Science and Technology" 60092945');
	
	define('WOS_CONTROLLED_ORG_NAME', 'King Abdullah University of Science Technology');

	// List of item types to have relations transferred to DSpace
	define('HANDLING_RELATIONS', array('Dataset', 'Data File', 'Bioproject', 'Software'));

	define('UPLOAD_FILE_PATH', '/data/www/irts/public_html/upload/');

	define('NEW_ORG_FILE_DIRECTORY', ''); // '/data/www/irts/imports/new/Organizations' on lthlibtest, '/home/sftpsap/patron_info/' on prod
	define('OLD_ORG_FILE_DIRECTORY', '/data/www/irts/imports/processed/Organizations');

	define('SDAIA_EXPORT_DIRECTORY', '/data/www/irts/exports/forSDAIA/');

	define('POWER_AUTOMATE_SET_EMAIL_CATEGORY_AS_COMPLETE_ENDPOINT', 'https://prod-107.westeurope.logic.azure.com:443/workflows/28eeade50184426d8c43e1eb7deca9bc/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=1Q_TyaSrhp_Gobrh6PdXBjlhheQ6VKevSkA77cnTD2o');

	// github API
	define('GITHUB_API', 'https://api.github.com/repos/');

	define('NCBI_API_URL', 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/');

	define('OAPOLICY_START_YEAR', date("Y-m-d", strtotime(OAPOLICY_START_DATE)));
	
	define('YEARS_UNDER_OA_POLICY', range(OAPOLICY_START_YEAR, CURRENT_YEAR));
	
	define('PUBLICATION_TRACKING_START_YEAR', date("Y-m-d", strtotime(PUBLICATION_TRACKING_START_DATE)));

	define('YEARS_TO_TRACK', range(PUBLICATION_TRACKING_START_YEAR, CURRENT_YEAR));
