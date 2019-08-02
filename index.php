<?php
session_start();
define ( 'CURL_WEEE_AGENT', 'curl Weee v1.0' );

function _call_admin_report_api($method, $params = array()) {
	$url = 'https://' . $_SERVER ['SERVER_NAME'] . ':' . $_SERVER ["SERVER_PORT"] . '/admin_report/' . $method;
	$fields_string = '';
	foreach ( $params as $key => $value ) {
		$fields_string .= $key . '=' . urlencode ( $value ) . '&';
	}
	rtrim ( $fields_string, '&' );
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_USERAGENT, CURL_WEEE_AGENT );
	curl_setopt ( $ch, CURLOPT_POST, count ( $params ) );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields_string );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	if (isset ( $_COOKIE ["ci_session"] )) {
		curl_setopt ( $ch, CURLOPT_COOKIE, 'ci_session=' . urlencode ( $_COOKIE ["ci_session"] ) );
	} else {
		curl_setopt ( $ch, CURLOPT_COOKIE, 'ci_session=' );
	}
	$result_json = curl_exec ( $ch );
	curl_close ( $ch );
	return json_decode ( $result_json );
}

if (isset ( $_GET ["report"] )) {
	$params = array (
			"report" => $_GET ["report"]
	);
	$check_result = _call_admin_report_api ( "api_report_check_session", $params )->result;
	if ($check_result != 'OK') {
		if (isset ( $_SERVER ['HTTP_HOST'] )) {
			$base_url = isset ( $_SERVER ['HTTPS'] ) && strtolower ( $_SERVER ['HTTPS'] ) !== 'off' ? 'https' : 'http';
			$base_url .= '://' . $_SERVER ['HTTP_HOST'];
			$domain = $base_url ;
			$base_url .= str_replace ( basename ( $_SERVER ['SCRIPT_NAME'] ), '', $_SERVER ["REQUEST_URI"] );
		} 

		else {
			$base_url = 'https://localhost/';
			$domain = $base_url ;
		}
		if( $check_result == 'report_no_premission' ) {
			header( "Location: {$domain}/error/error_401" ) ;
			exit ();
		}else{
			$uri = '/login?redirect_url=' . urlencode ( $base_url ) . "&err_key=" . $check_result;
			$http_response_code = "302";
			header ( "Location: " . $uri, TRUE, $http_response_code );
			exit ();
		}
	}
}

//set php ini so the page doesn't time out for long requests
ini_set('max_execution_time', 300);

//sets up autoloading of composer dependencies
include 'vendor/autoload.php';

//sets up autoload (looks in classes/local/, classes/, and lib/ in that order)
require 'lib/PhpReports/PhpReports.php';

header("Access-Control-Allow-Origin: *");

// Google Analytics API
if(isset(PhpReports::$config['ga_api'])) {
  $ga_client = new Google_Client();
  $ga_client->setApplicationName(PhpReports::$config['ga_api']['applicationName']);
  $ga_client->setClientId(PhpReports::$config['ga_api']['clientId']);
  $ga_client->setAccessType('offline');
  $ga_client->setClientSecret(PhpReports::$config['ga_api']['clientSecret']);
  $ga_client->setRedirectUri(PhpReports::$config['ga_api']['redirectUri']);
  $ga_service = new Google_Service_Analytics($ga_client);
  $ga_client->addScope(Google_Service_Analytics::ANALYTICS);
  if(isset($_GET['code'])) {
    $ga_client->authenticate($_GET['code']);
    $_SESSION['ga_token'] = $ga_client->getAccessToken();
    
    if(isset($_SESSION['ga_authenticate_redirect'])) {
      $url = $_SESSION['ga_authenticate_redirect'];
      unset($_SESSION['ga_authenticate_redirect']);
      header("Location: $url");
      exit;
    }
  }
  if(isset($_SESSION['ga_token'])) {    
    $ga_client->setAccessToken($_SESSION['ga_token']);
  }
  elseif(isset(PhpReports::$config['ga_api']['accessToken'])) {    
    $ga_client->setAccessToken(PhpReports::$config['ga_api']['accessToken']);
    $_SESSION['ga_token'] = $ga_client->getAccessToken();
  }
  
  Flight::route('/ga_authenticate',function() use($ga_client) {
    $authUrl = $ga_client->createAuthUrl();
    if(isset($_GET['redirect'])) {
      $_SESSION['ga_authenticate_redirect'] = $_GET['redirect'];
    }
    header("Location: $authUrl");
    exit;
  });
}

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

Flight::route('/set-environment',function() {
    header("Content-Type: application/json");
	$_SESSION['environment'] = $_REQUEST['environment'];

    echo '{ "status": "OK" }';
});

//email report
Flight::route('/email',function() {
	PhpReports::emailReport();	
});

Flight::set('flight.handle_errors', false);
Flight::set('flight.log_errors', true);

Flight::start();
