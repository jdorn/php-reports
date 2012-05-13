<?php
session_start();
require 'lib/PhpReports/PhpReports.php';

Flight::route('/',array('PhpReports','listReports'));

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

Flight::start();
