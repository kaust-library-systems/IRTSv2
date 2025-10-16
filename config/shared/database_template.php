<?php
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	
	$irts = new mysqli(MYSQL_SERVER_IP, MYSQL_USER, MYSQL_PW, IRTS_DATABASE);
	
	$ioi = new mysqli(MYSQL_SERVER_IP, MYSQL_USER, MYSQL_PW, IOI_DATABASE);

	$doiMinter = new mysqli(MYSQL_SERVER_IP, MYSQL_USER, MYSQL_PW, DOIMINTER_DATABASE);

	$ga = new mysqli(MYSQL_SERVER_IP, MYSQL_USER, MYSQL_PW, GOOGLE_ANALYTICS_DATABASE);

	$repository = new mysqli(MYSQL_SERVER_IP, MYSQL_USER, MYSQL_PW, REPOSITORY_DATABASE);
	
	$irts->set_charset("utf8mb4");
	
	$ioi->set_charset("utf8mb4");

	$doiMinter->set_charset("utf8mb4");

	$ga->set_charset("utf8mb4");

	$repository->set_charset("utf8mb4");