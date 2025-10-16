<?php
	// change it to 0 when moving to production, this prevents details of some warnings and errors from being directly displayed to users
	ini_set('display_errors', 1);

    // keep consistent character encoding settings in scripts and databases
	ini_set('default_charset', 'UTF-8' );
	mb_internal_encoding("UTF-8");