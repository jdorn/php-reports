<?php
//sets up autoloading of composer dependencies
include 'vendor/autoload.php';

//sets up autoload (looks in classes/local/, classes/, and lib/ in that order)
require 'lib/PhpReports/PhpReports.php';


require_once("login/models/config.php");

// Public page

setReferralPage(getAbsoluteDocumentPath(__FILE__));

//Forward the user to their default page if he/she is already logged in
if(isUserLoggedIn() || PhpReports::$config['loginEnable'] == 0) {
	
	session_start();
	
	ini_set('memory_limit', '512M');
	
	//set php ini so the page doesn't time out for long requests
	ini_set('max_execution_time', 300);
	

	Flight::route('/',function() {
		PhpReports::listReports();
	});
	
	Flight::route('/dashboards',function() {
		PhpReports::listDashboards();
	});
	
	Flight::route('/dashboard/@name',function($name) {
		PhpReports::displayDashboard($name);
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
	
	Flight::route('/add',function() {
		PhpReports::addReport($_REQUEST['report']);
	});
	
	Flight::route('/set-environment',function() {
	    header("Content-Type: application/json");
		$_SESSION['environment'] = $_REQUEST['environment'];
	
	    echo '{ "status": "OK" }';
	});
	
	//email report
	Flight::route('/email',function() {
		PhpReports::emailReport();	
	});
	
	//email report
	Flight::route('/email_scheduler',function() {
		PhpReports::scheduleEmail();	
	});
	
	
	Flight::start();
}else{
	header("Location: login");
	exit();
}
