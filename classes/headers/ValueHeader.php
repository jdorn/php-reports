<?php
class ValueHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		if(strpos($value,',') === false) {
			throw new Exception("Invalid value '$value'");
		}
		list($name,$value) = explode(',',$value);
		$var = trim($name);
		$default = trim($value);
		
		if(isset($report->options['Variables'][$var])) {
			if($report->macros[$var]) return;
			
			$report->options['Variables'][$var]['default'] = $default;
			$report->macros[$var] = $default;
		}
	}
	
	public static function afterParse(&$report) {
		foreach($report->options['Includes'] as $included_report) {
			$report->importHeaders($included_report,'Value');
		}
	}
}
