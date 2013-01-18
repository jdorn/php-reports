<?php
class MongoReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." database defined for environment '".$report->options['Environment']."'");
		}
		
		$mongo = $environments[$report->options['Environment']][$report->options['Database']];
		
		//default host macro to mysql's host if it isn't defined elsewhere
		if(!isset($report->macros['host'])) $report->macros['host'] = $mongo['host'];
		
		//if there are any included reports, add it to the top of the raw query
		if(isset($report->options['Includes'])) {
			$included_code = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_code .= trim($included_report->raw_query)."\n";
			}
			
			$report->raw_query = $included_code . $report->raw_query;
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = '';
		foreach($report->macros as $key=>$value) {
			if(is_array($value)) {
				$value = json_encode($value);
			}
			else {
				$value = '"'.addslashes($value).'"';
			}
			
			$eval .= 'var '.$key.' = '.$value.';'."\n";
		}
		$eval .= $report->raw_query;

		$environments = PhpReports::$config['environments'];
		$config = $environments[$report->options['Environment']][$report->options['Database']];
		
		$mongo_database = isset($report->options['Mongodatabase'])? $report->options['Mongodatabase'] : '';

		//command without eval string
		$command = 'mongo '.$config['host'].':'.$config['port'].'/'.$mongo_database.' --quiet --eval ';

		//easy to read formatted query
		$report->options['Query_Formatted'] = '<div>
			<pre style="background-color: black; color: white; padding: 10px 5px;">$ '.$command.'"..."</pre>'.
			'Eval String:'.
			'<pre class="prettyprint linenums lang-js">'.htmlentities($eval).'</pre>
		</div>';

		//escape the eval string and add it to the command
		$command .= escapeshellarg($eval);
		$report->options['Query'] = '$ '.$command;

		//include stderr so we can capture shell errors (like "command mongo not found")
		$result = shell_exec($command.' 2>&1');
		
		$result = trim($result);
		
		$json = json_decode($result, true);
		if($json === NULL) throw new Exception($result);
		
		return $json;
	}
}
