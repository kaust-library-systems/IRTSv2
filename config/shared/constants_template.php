<?php
	//Use TEST servers and databases?
	define('IRTS_TEST', TRUE); // change to FALSE when using in production

	//MySQL Server IP Address
	define('MYSQL_SERVER_IP', "10.(...).29");	
	
	//constants that may differ between testing and production
	if(IRTS_TEST) {
		define('REPOSITORY_BASE_URL', '(...)com');

		//Database names
		define('IRTS_DATABASE', 'test_irts');
		define('IOI_DATABASE', 'test_ioi');
		define('DOIMINTER_DATABASE', 'test_doiMinter');
		
		//raw downloads and page views data from Google Analytics 4, Universal Analytics, or SOLR
		define('GOOGLE_ANALYTICS_DATABASE', 'test_GA');

		//cleaned up repository related data from multiple sources for reporting, especially use in PowerBI
		define('REPOSITORY_DATABASE', 'test_repository');

		//ORCID icon bitstream id
		define('ORCID_ICON_UUID', '8(...)6');

		//ETD Community UUID
		define('ETD_COMMUNITY_UUID', '1(...)b');
	}
	else{
		define('REPOSITORY_BASE_URL', 're(...).sa');

		//Database names
		define('IRTS_DATABASE', 'prod_irts');
		define('IOI_DATABASE', 'prod_ioi');
		define('DOIMINTER_DATABASE', 'prod_doiMinter');

		//raw downloads and page views data from Google Analytics 4, Universal Analytics, or SOLR
		define('GOOGLE_ANALYTICS_DATABASE', 'prod_GA');

		//cleaned up repository related data from multiple sources for reporting, especially use in PowerBI
		define('REPOSITORY_DATABASE', 'prod_repository');

		//ORCID icon bitstream id
		define('ORCID_ICON_UUID', '8(...)4');

		//ETD Community UUID
		define('ETD_COMMUNITY_UUID', 'd(...)b');
	}	

	define('REPOSITORY_URL', 'https://'.REPOSITORY_BASE_URL);
	
	define('REPOSITORY_API_URL', REPOSITORY_URL.'/server/api/');
	
	define('REPOSITORY_OAI_URL', REPOSITORY_URL.'/server/oai/request?');
	
	define('REPOSITORY_OAI_ID_PREFIX', 'oai:'.REPOSITORY_BASE_URL.':');

	define('ORCID_ICON_URL', REPOSITORY_API_URL.'core/bitstreams/'.ORCID_ICON_UUID.'/content');
	
	//Locally Defined Constants
	define('INSTITUTION_ABBREVIATION', 'KAUST');
	
	define('INSTITUTION_NAME', 'K(...)y');
	
	define('INSTITUTION_CITY', 'Thuwal');
	
	define('IR_EMAIL', 'r(...).sa');
	
	define('LOCAL_PERSON_FIELD', 'kaust.person');
	
	define('ORCID_ENABLED_FIELDS', array('dc.contributor.author','dc.contributor.advisor','dc.contributor.committeemember','dc.contributor.editor'));
	
	define('DOI_BASE_URL', 'https://doi.org/');
	
	//LDAP constants
	define('LDAP_ACCOUNT_SUFFIX', '(...)'); //binding parameters

	define('LDAP_HOSTNAME_SSL', 'ldaps://w(...)6'); // space-separated list of valid hostnames for failover

	define('LDAP_BASE_DN', '(...)=SA');

	define('LDAP_PERSON_ID_ATTRIBUTE', 'e(...)5');

	define('LDAP_EMAIL_ATTRIBUTE', 'mail');
	
	define('LDAP_NAME_ATTRIBUTE', 'displayName');

	define('LDAP_TITLE_ATTRIBUTE', 'title');

	//Common Constants
	define('TODAY', date("Y-m-d"));
	
	define('NOW', date("Y-m-d H:i:s"));

	define('YESTERDAY', date("Y-m-d", strtotime("-1 days")));

	define('TOMORROW', date("Y-m-d", strtotime("+1 days")));

	define('ONE_WEEK_AGO', date("Y-m-d", strtotime("-7 days")));
	
	define('ONE_WEEK_LATER', date("Y-m-d", strtotime("+7 days")));
	
	define('ONE_MONTH_AGO', date("Y-m-d", strtotime("-1 months")));

	define('THREE_MONTHS_AGO', date("Y-m-d", strtotime("-3 months")));
	
	define('ONE_YEAR_AGO', date("Y-m-d", strtotime("-1 years")));
	
	define('ONE_YEAR_LATER', date("Y-m-d", strtotime("+1 years")));	
	
	define('CURRENT_YEAR', date("Y"));
