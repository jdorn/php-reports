<?php
session_start();

if(isset($_GET['report'])) {
	$report = $_GET['report'];
}
else {
	throw new Exception("No report in GET string");
}

$database = null;
if(isset($_REQUEST['database'])) {
	$database = $_REQUEST['database'];
	
	//store this database selection in the session
	$_SESSION['database'] = $database;
}
elseif(isset($_SESSION['database'])) {
	$database = $_SESSION['database'];
}

$macros = array();
if(isset($_GET['macro_names'])) {
	$macros = array_combine($_GET['macro_names'],$_GET['macro_values']);
}

require_once('lib/Class/Report.php');
$report = new Report($report,$macros,$database);

//add csv download link to report
$report->options['csv_link'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&csv=true';

//if exporting to csv, use the csv template instead of the report template
if(isset($_REQUEST['csv'])) $report->options['Template'] = 'csv';

$page_template = array();

$page_template['title'] = $report->options['Name'];
$page_template['variable_form'] = $report->renderVariableForm();

if(isset($_REQUEST['raw'])) {
	$page_template['content'] = '<pre>'.$report->getRaw().'</pre>';
}

if(!$report->is_ready) {
	$page_template['notice'] = "The report needs more information before running.";
}
else {
	try {
		$page_template['report'] = $report->renderReport();
	}
	catch(Exception $e) {
		$page_template['error'] = $e->getMessage();
		if(isset($report->options['Query_Formatted'])) $page_template['content'] = $report->options['Query_Formatted'];
	}
}

require_once('lib/Mustache/Mustache.php');
$m = new Mustache;

if(isset($_REQUEST['csv'])) {
	$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);
	
	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=".$file_name.".csv");
	header("Pragma: no-cache");
	header("Expires: 0");

	$page_template_file = 'csv_page';
}
else {	
	$page_template_file = 'page';
}

$page_template_source = file_get_contents('templates/'.$page_template_file.'.html');
echo $m->render($page_template_source,$page_template);
?>
