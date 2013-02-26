<?php
session_start();

//set php ini so the page doesn't time out for long requests
ini_set('max_execution_time', 300);

//sets up autoloading of composer dependencies
include 'vendor/autoload.php';

//sets up autoload (looks in classes/local/, classes/, and lib/ in that order)
require 'lib/PhpReports/PhpReports.php';

Flight::route('/',function() {
	PhpReports::listReports();
});

//JSON list of reports (used for typeahead search)
Flight::route('/report-list-json',function() {
	header("Content-Type: application/json");
	header("Cache-Control: max-age=3600");

	echo PhpReports::getReportListJSON();
});

//if no report format is specified, default to html
Flight::route('/report',function() {
	PhpReports::displayReport($_REQUEST['report'],'html');
});

//reports in a specific format (e.g. 'html','csv','json','xml', etc.)
Flight::route('/report/@format',function($format) {
	PhpReports::displayReport($_REQUEST['report'],$format);
});

Flight::route('/edit',function() {
	PhpReports::editReport($_REQUEST['report']);
});

Flight::route('/set-environment',function() {
	$_SESSION['environment'] = $_REQUEST['environment'];
});

//email report
Flight::route('/email',function() {
	PhpReports::emailReport();	
});


Flight::start();
