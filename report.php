<?php
require_once('config.php');

if(isset($_GET['report'])) {
	$report = $_GET['report'];
}
else {
	exit("No report in get string");
}

if(!file_exists('reports/'.$report)) {
	exit("Report not found");
}

//get the report source and split into comments and code
$source = file_get_contents('reports/'.$report);
$source = str_replace(array("\r\n","\r"),"\n",$source);
list($headers,$code) = explode("\n\n",$source,2);
$headers = explode("\n",$headers);

//extract options and variables
$options = array();
$vars = array();
$macros = array();
$report_ready = true;
foreach($headers as $option) {
	//remove comment characters from the start of the line
	$option = ltrim($option,'/-*');
	
	if(empty($option)) continue;
	
	list($name,$value) = explode(':',$option,2);
	
	if(strtolower(substr(trim($name),0,4))=='var ') {
		$name = trim(substr(trim($name),4));
		$value = json_decode($value,true);
		if(!$value) $value = array();
		
		if(isset($_GET['var_'.$name])) $macros[$name] = $_GET['var_'.$name];
		elseif(isset($value['default'])) $macros[$name] = $value['default'];
		else {
			$macros[$name] = '';
			$report_ready = false;
		}
		
		$vars[$name] = $value;
	}
	else {
		$options[trim($name)] = trim($value);
	}
}

//if the type option isn't set, try to infer it from the file type
if(!isset($options['Type'])) {
	$file_type = array_pop(explode('.',$report));
	switch($file_type) {
		case 'js':
			$options['Type'] = 'mongo';
			break;
		case 'sql':
			$options['Type'] = 'mysql';
			break;
		default:
			exit("Unknown report type");
	}
}

if($vars) {
	?>
	<form method='get'>
		<input type='hidden' name='report' value='<?php echo $_GET['report']; ?>' />
	<?php
	foreach($vars as $key=>$value) {
		if(!isset($value['type'])) $value['type'] = 'string';
	?>
	<div>
		<label for='var_<?php echo $key; ?>'><?php echo (isset($value['name'])? $value['name'] : $key); ?></label>
		<input type='text' name='var_<?php echo $key; ?>' value='<?php echo $macros[$key]; ?>' />	
	</div>
	<?php
	}
	?>
	<input type='submit' value='Run Report' />
	</form>
	<hr />
	<?php
}


if(!$report_ready) {
	exit("The report needs more information before running.");
}

function replace_macros($string,$macros=array()) {
	foreach($macros as $name=>$value) {
		$string = str_replace('{{'.$name.'}}',$value,$string);
	}
	return $string;
}

$rows = array();

?>
<h1><?php echo $options['Name']; ?></h1>
<p><?php echo $options['Description']; ?></p>
<?php
if($options['Type'] === 'mysql') {
	$sql = replace_macros($code,$macros);
	
	$config = current(array_keys($mysql_connections));
	if(isset($_SESSION['mysql_connection'])) $config = $_SESSION['mysql_connection'];
	if(isset($_REQUEST['mysql_connection'])) $config = $_REQUEST['mysql_connection'];
	
	if(!isset($mysql_connections[$config])) {
		exit('Invalid mysql configuration');
	}
	
	if(!mysql_connect($mysql_connections[$config]['host'], $mysql_connections[$config]['username'], $mysql_connections[$config]['password'])) {
		exit('Could not connect to Mysql');
	}
	if(!mysql_select_db($mysql_connections[$config]['database'])) {
		exit('Could not select Mysql database');
	}

	$result = mysql_query($sql);
	
	if(!$result) {
		exit('Query failed: '.mysql_error());
	}
	
	while($row = mysql_fetch_assoc($result)) {
		$rows[] = $row;
	}
}
elseif($options['Type'] === 'mongo') {
	$eval = '';
	foreach($macros as $key=>$value) {
		$eval .= 'var '.$key.' = \"'.$value.'\";';
	}
	$command = 'mongo localhost/'.$options['Database'].' --eval "'.$eval.'" '.$report;
	echo $command;
	exit();
}



//output report
$header = isset($options['Headers'])? explode(',',$options['Headers']) : array_keys(current($rows));
?>
<table border=1 cellpadding=5>
	<tr>
		<?php
		foreach($header as $name) {
			?>
			<th><?php echo $name; ?></th>
			<?php
		}
		?>
	</tr>
	<?php
	foreach($rows as $row) {
	?>
	<tr>
		<?php foreach($row as $value) {
		?>
		<td><?php echo $value; ?></td>
		<?php
		}
		?>
	</tr>
	<?php
	}
	?>
</table>
