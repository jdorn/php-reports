<?php
class PhpReportType extends ReportTypeBase {
	public static function init(&$report) {
		$report->raw_query = "<?php\n//REPORT: ".$report->report."\n".trim($report->raw_query);
		
		//if there are any included reports, add it to the top of the raw query
		if(isset($report->options['Includes'])) {
			$included_code = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_code .= "\n<?php /*BEGIN INCLUDED REPORT*/ ?>".trim($included_report->raw_query)."<?php /*END INCLUDED REPORT*/ ?>";
			}
			
			if($included_code) $included_code.= "\n";
			
			$report->raw_query = $included_code . $report->raw_query;
			
			//make sure the raw query has a closing PHP tag at the end
			//this makes sure it will play nice as an included report
			if(!preg_match('/\?>\s*$/',$report->raw_query)) $report->raw_query .= "\n?>";
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}

	public static function getVariableOptions($params, &$report)
	{

		if (is_array($params)) {

			// support for dynamic select type using database_options in php reports
			// only supporting mysql currently:

			if (stristr($params['type'], 'mysql')) {

				// create a clone of our report for a mysql connection to not pollute
				// the original object. This may not be necessary.
				$reportMysql = clone $report;

				// Connect to the database since this we are using a different type.
				// This also allows for setting the database with the varaible string.
				$reportMysql->options['Database'] = isset($params['database']) ? $params['database'] : 'mysql';
				MysqlReportType::openConnection($reportMysql);

				return MysqlReportType::getVariableOptions($params, $reportMysql);
			}
		}

		return array();
	}

	public static function run(&$report) {		
		$eval = "<?php /*BEGIN REPORT MACROS*/ ?><?php ";
		foreach($report->macros as $key=>$value) {
			$value = var_export($value,true);
			
			$eval .= "\n".'$'.$key.' = '.$value.';';
		}
		$eval .= "\n?><?php /*END REPORT MACROS*/ ?>".$report->raw_query;
		
		$config = PhpReports::$config;
		
		//store in both $database and $environment for backwards compatibility
		$database = PhpReports::$config['environments'][$report->options['Environment']];
		$environment = $database;
		
		$report->options['Query'] = $report->raw_query;
		
		$parts = preg_split('/<\?php \/\*(BEGIN|END) (INCLUDED REPORT|REPORT MACROS)\*\/ \?>/',$eval);
		$report->options['Query_Formatted'] = '';
		$code = htmlentities(trim(array_pop($parts)));
		$linenum = 1;
		foreach($parts as $part) {
			if(!trim($part)) continue;

			//get name of report
			$name = preg_match("|//REPORT: ([^\n]+)\n|",$part,$matches);

			if(!$matches) {
				$name = "Variables";
			}
			else {
				$name = $matches[1];
			}

			$report->options['Query_Formatted'] .= '<div class="included_report" data-name="'.htmlentities($name).'">';
			$report->options['Query_Formatted'] .= "<pre class='prettyprint lang-php linenums:".$linenum."'>".htmlentities(trim($part))."</pre>";
			$report->options['Query_Formatted'] .= "</div>";
			$linenum += count(explode("\n",trim($part)));
		}
		
		$report->options['Query_Formatted'] .= '<pre class="prettyprint lang-php linenums:'.$linenum.'">'.$code.'</pre>';

		ob_start();
		ini_set('display_errors','Off');
		eval('?>'.$eval);
		$result = ob_get_contents();
		ob_end_clean();
		ini_set('display_errors','On');

		$result = trim($result);
		
		$json = json_decode($result, true);
		if($json === NULL) throw new Exception($result);
		
		return $json;
	}
}
