<?php
class VariableHeader implements HeaderInterface {
	//in format: variable, params
	//params can be a JSON object or "name" or "name, options"
	//	'name', 
	//	'type' (text, select, textarea, date),
	//	'options' (for drop down choices.  array or '|' separated list)
	//	'default' (the default value)
	//	'empty' (default false.  if true, value can be empty)
	public static function parse($key, $value, &$report) {
		list($var,$params) = explode(',',$value,2);
		$var = trim($var);
		$params = trim($params);
		
		//json params
		if($temp = json_decode($params,true)) {
			$params = $temp;
		}
		//"name" or "name, options" style
		else {
			$parts = explode(',',$params);
			$params = array(
				'name'=>$parts[0]
			);
			
			//name, options style
			if(isset($parts[1])) {
				$params['type'] = 'select';
				$params['options'] = explode('|',$parts[1]);
			}
		}
		
		//add to options
		if(!isset($report->options['Variables'])) $report->options['Variables'] = array();
		$report->options['Variables'][$var] = $params;
		
		//add to macros
		if(!isset($report->macros[$var]) && isset($params['default'])) {
			$report->macros[$var] = $params['default'];
		}
		elseif(!isset($report->macros[$var])) {
			$report->macros[$var] = '';
		}
		
		//if the macro value is empty and empty isn't allowed
		//mark the report as not ready to stop it being run
		if(trim($report->macros[$var])==='' && (!isset($params['empty']) || !$params['empty'])) {
			$report->is_ready =false;
		}
	}
}
