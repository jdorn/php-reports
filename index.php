<?php
session_start();

//set php ini so the page doesn't time out for long requests
ini_set('memory_limit', -1);
ini_set('max_execution_time', 10800);
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

//sets up autoload (looks in classes/local/, classes/, and lib/ in that order)
require 'lib/PhpReports/PhpReports.php';

Flight::route('/',function() {
	PhpReports::listReports();
});

//if no report format is specified, default to html
Flight::route('/report',function() {
	PhpReports::displayReport($_REQUEST['report'],'html');
});

//reports in a specific format (e.g. 'html','csv','json','xml', etc.)
Flight::route('/report/@format',function($format) {
	PhpReports::displayReport($_REQUEST['report'],$format);
});

//reports in a specific format (e.g. 'html','csv','json','xml', etc.)
Flight::route('/edit',function() {
	PhpReports::editReport($_REQUEST['report']);
});

Flight::start();
