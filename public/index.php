<?php
// for build-in php server serve the requested resource as-is.
if (php_sapi_name() == 'cli-server' && preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $_SERVER["REQUEST_URI"])) {
    return false;
}

session_start();

//set php ini so the page doesn't time out for long requests
ini_set('max_execution_time', 300);

//sets up autoloading of composer dependencies
include '../vendor/autoload.php';

use PhpReports\PhpReports;

header("Access-Control-Allow-Origin: *");

// Google Analytics API
if (isset(PhpReports::$config['ga_api'])) {
    $ga_client = new Google_Client();
    $ga_client->setApplicationName(PhpReports::$config['ga_api']['applicationName']);
    $ga_client->setClientId(PhpReports::$config['ga_api']['clientId']);
    $ga_client->setAccessType('offline');
    $ga_client->setClientSecret(PhpReports::$config['ga_api']['clientSecret']);
    $ga_client->setRedirectUri(PhpReports::$config['ga_api']['redirectUri']);
    $ga_service = new Google_Service_Analytics($ga_client);
    $ga_client->addScope(Google_Service_Analytics::ANALYTICS);
    if (isset($_GET['code'])) {
        $ga_client->authenticate($_GET['code']);
        $_SESSION['ga_token'] = $ga_client->getAccessToken();

        if (isset($_SESSION['ga_authenticate_redirect'])) {
            $url = $_SESSION['ga_authenticate_redirect'];
            unset($_SESSION['ga_authenticate_redirect']);
            header("Location: $url");
            exit;
        }
    }
    if (isset($_SESSION['ga_token'])) {
        $ga_client->setAccessToken($_SESSION['ga_token']);
    } elseif (isset(PhpReports::$config['ga_api']['accessToken'])) {
        $ga_client->setAccessToken(PhpReports::$config['ga_api']['accessToken']);
        $_SESSION['ga_token'] = $ga_client->getAccessToken();
    }

    Flight::route('/ga_authenticate', function () use ($ga_client) {
        $authUrl = $ga_client->createAuthUrl();
        if (isset($_GET['redirect'])) {
            $_SESSION['ga_authenticate_redirect'] = $_GET['redirect'];
        }
        header("Location: $authUrl");
        exit;
    });
}

Flight::route('GET /', function () {
    PhpReports::listReports();
});

Flight::route('GET /dashboards', function () {
    PhpReports::listDashboards();
});

Flight::route('GET /dashboard/@name', function ($name) {
    PhpReports::displayDashboard($name);
});

//JSON list of reports (used for typeahead search)
Flight::route('GET /report-list-json', function () {
    $reports = PhpReports::getReportList();
    Flight::response()->header('Cache-Control', 'max-age=86400, public');
    Flight::response()->header('Pragma', '');
    Flight::etag(substr(md5(serialize($reports)), 0, 15));
    Flight::json($reports);
});

//if no report format is specified, default to html
Flight::route('/report', function () {
    PhpReports::displayReport($_REQUEST['report'], 'html');
});

//reports in a specific format (e.g. 'html','csv','json','xml', etc.)
Flight::route('/report/@format', function ($format) {
    PhpReports::displayReport($_REQUEST['report'], $format);
});

Flight::route('/edit', function () {
    PhpReports::editReport($_REQUEST['report']);
});

Flight::route('GET|POST /set-environment', function () {
    $request = Flight::request();
    $environment = array_filter([
        array_key_exists('environment', $request->query->getData()) ? $request->query['environment'] : null,
        array_key_exists('environment', $request->data->getData()) ? $request->data['environment'] : null
    ]);

    $environment = array_pop($environment);

    $_SESSION['environment'] = $environment;

    Flight::json(['status' => 'OK']);
}, true);

//email report
Flight::route('/email', function () {
    PhpReports::emailReport();
});

Flight::set('flight.handle_errors', false);
Flight::set('flight.log_errors', true);

PhpReports::init();

Flight::start();
