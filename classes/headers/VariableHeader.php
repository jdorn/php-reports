<?php
class VariableHeader extends HeaderBase {
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
		//legacy format
		else {
			$parts = explode(',',$params);
			$params = array(
				'name'=>$parts[0]
			);
			
			//more information than just name and options
			if(isset($parts[1])) {
				//name, LIST, options style (multiselect)
				if(trim($parts[1]) === "LIST" && isset($parts[2])) {
					$params['type'] = 'select';
					$params['multiple'] = true;
					$params['options'] = explode('|',$parts[2]);
				}
				//name, LIST style (textarea)
				elseif(trim($parts[1]) === "LIST") {
					$params['multiple'] = true;
					if(isset($report->macros[$var])) $report->macros[$var] = explode("\n",trim($report->macros[$var]));
				}
				//name, options style (select)
				else {
					$params['type'] = 'select';
					$params['options'] = explode('|',$parts[1]);
				}
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
			if($params['multiple']) $report->macros[$var] = array();
			else $report->macros[$var] = '';
		}
		
		//macros shortcuts for arrays
		if(isset($params['multiple']) && $params['multiple']) {
			//allow support for {macro} instead of {{#macro}}{{^first}},{{/first}}'{{{value}}}'{{/macro}}
			$report->raw_query = preg_replace('/([^\{])\{([a-zA-Z0-9_\-]+)\}([^\}])/','$1{{#$2}}{{^first}},{{/first}}\'{{{value}}}\'{{/$2}}$3',$report->raw_query);
		
			//allow support for {(macro)} instead of {{#macro}}{{^first}},{{/first}}('{{{value}}}'){{/macro}}
			//this is shorthand for quoted, comma separated lists
			$report->raw_query = preg_replace('/([^\{])\{\(([a-zA-Z0-9_\-]+)\)\}([^\}])/','$1{{#$2}}{{^first}},{{/first}}(\'{{{value}}}\'){{/$2}}$3',$report->raw_query);
		}
		//macros sortcuts for non-arrays
		else {
			//allow support for {macro} instead of {{{macro}}} for legacy support
			$report->raw_query = preg_replace('/([^\{])(\{[a-zA-Z0-9_\-]+\})([^\}])/','$1{{$2}}$3',$report->raw_query);
		}
		
		//if the macro value is empty and empty isn't allowed
		//mark the report as not ready to stop it being run
		if(trim($report->macros[$var])==='' && (!isset($params['empty']) || !$params['empty'])) {
			$report->is_ready =false;
		}
	}
}
