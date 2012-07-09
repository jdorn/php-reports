<?php
abstract class PhpReportType extends ReportTypeBase {
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
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = "<?php /*BEGIN REPORT MACROS*/ ?><?php ";
		foreach($report->macros as $key=>$value) {
			$value = var_export($value,true);
			
			$eval .= "\n".'$'.$key.' = '.$value.';';
		}
		$eval .= "\n?><?php /*END REPORT MACROS*/ ?>".$report->raw_query;
		
		$config = PhpReports::$config;
		$database = PhpReports::$config['databases'][$report->options['Database']];
		
		$report->options['Query'] = $report->raw_query;
		
		$parts = preg_split('/<\?php \/\*(BEGIN|END) (INCLUDED REPORT|REPORT MACROS)\*\/ \?>/',$eval);
		$formatted = '';
		$code = '<div style="margin: 10px 0;">'.highlight_string(array_pop($parts),true).'</div>';
		foreach($parts as $part) {
			if(!trim($part)) continue;
			$formatted .= "<div class='included_report'>".highlight_string($part, true)."</div>";
		}
		$formatted .= $code;
		
		$report->options['Query_Formatted'] = '<div><pre style="border-left: 1px solid black; padding-left: 20px;">'.$formatted.'</pre></div>';		
		
		ob_start();
		eval('?>'.$eval);
		$result = ob_get_contents();
		ob_end_clean();
		
		$json = json_decode($result, true);
		if(!$json) throw new Exception($result);
		
		return $json;
	}
}
