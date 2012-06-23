<?php
class ValueHeader extends HeaderBase {
	static $validation = array(
		'name'=>array(
			'required'=>true,
			'type'=>'string'
		),
		'value'=>array(
			'required'=>true
		)
	);
	
	public static function init($params, &$report) {
		if(isset($report->options['Variables'][$params['name']])) {
			if($report->macros[$params['name']]) return;
			
			$report->options['Variables'][$params['name']]['default'] = $params['value'];
			$report->macros[$params['name']] = $params['value'];
		}
		else {
			throw new Exception("Providing value for unknown variable $params[name]");
		}
	}
	
	public static function parseShortcut($value) {
		if(strpos($value,',') === false) {
			throw new Exception("Invalid value '$value'");
		}
		list($name,$value) = explode(',',$value);
		$var = trim($name);
		$default = trim($value);
		
		return array(
			'name'=>$var,
			'value'=>$default
		);
	}
	
	public static function afterParse(&$report) {
		foreach($report->options['Includes'] as $included_report) {
			$report->importHeaders($included_report,'Value');
		}
	}
}
