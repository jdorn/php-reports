<?php
session_start();

ini_set('memory_limit', -1);
ini_set('max_execution_time', 10800);
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
		
require 'lib/PhpReports/PhpReports.php';

Flight::route('/',function() {
	PhpReports::listReports();
});

//shortcut for html report
Flight::route('/report',function() {
	PhpReports::htmlReport($_REQUEST['report']);
});

Flight::route('/report/html',function() {
	PhpReports::htmlReport($_REQUEST['report']);
});

Flight::route('/report/csv',function() {
	PhpReports::csvReport($_REQUEST['report']);
});

Flight::route('/report/text',function() {
	PhpReports::textReport($_REQUEST['report']);
});

Flight::route('/report/json',function() {
	PhpReports::jsonReport($_REQUEST['report']);
});

Flight::route('/report/sql',function() {
	PhpReports::sqlReport($_REQUEST['report']);
});

Flight::route('/report/xml',function() {
	PhpReports::xmlReport($_REQUEST['report']);
});

Flight::route('/report/raw',function() {
	PhpReports::rawReport($_REQUEST['report']);
});

Flight::route('/report/debug',function() {
	PhpReports::debugReport($_REQUEST['report']);
});

Flight::start();
