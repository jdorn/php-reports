<?php
if(isset($_GET['report'])) {
	$report = $_GET['report'];
}
else {
	throw new Exception("No report in GET string");
}

$database = null;
if(isset($_REQUEST['database'])) $database = $_REQUEST['database'];
elseif(isset($_SESSION['database'])) $database = $_SESSION['database'];

$macros = array();
if(isset($_GET['macro_names'])) {
	$macros = array_combine($_GET['macro_names'],$_GET['macro_values']);
}

require_once('lib/Class/Report.php');
$report = new Report($report,$macros,$database);

echo $report->renderVariableForm();

if(!$report->is_ready) {
		echo "<p>The report needs more information before running.</p>";
		exit;
}

echo $report->renderReport();
?>
