<?php
session_start();
require 'lib/PhpReports/PhpReports.php';

Flight::route('/',array('PhpReports','listReports'));

Flight::route('/report',function() {
	PhpReports::displayReport($_REQUEST['report']);
});

Flight::start();
