<?php
class VariableHeader extends HeaderBase {
	
	static $validation = array(
		'name'=>array(
			'required'=>true,
			'type'=>'string'
		),
		'display'=>array(
			'type'=>'string'
		),
		'type'=>array(
			'type'=>'enum',
			'values'=>array('text','select','textarea','date'),
			'default'=>'text'
		),
		'options'=>array(
			'type'=>'array'
		),
		'default'=>array(
		
		),
		'empty'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'multiple'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'database_options'=>array(
			'type'=>'object'
		),
		'description'=>array(
			'type'=>'string'
		),
		'format'=>array(
			'type'=>'string',
			'default'=>'Y-m-d H:i:s'
		)
	);
	
	public static function init($params, &$report) {		
		if(!isset($params['display']) || !$params['display']) $params['display'] = $params['name'];
		
		if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/',$params['name'])) throw new Exception("Invalid variable name: $params[name]");
		
		//add to options
		if(!isset($report->options['Variables'])) $report->options['Variables'] = array();
		$report->options['Variables'][$params['name']] = $params;
		
		//add to macros
		if(!isset($report->macros[$params['name']]) && isset($params['default'])) {
			$report->addMacro($params['name'],$params['default']);
			
			$report->macros[$params['name']] = $params['default'];
		}
		elseif(!isset($report->macros[$params['name']])) {
			if($params['multiple']) $report->addMacro($params['name'],array());
			else $report->addMacro($params['name'],'');
		}		
		
		//macros shortcuts for arrays
		if(isset($params['multiple']) && $params['multiple']) {
			//allow support for {macro} instead of {{#macro}}{{^first}},{{/first}}'{{{value}}}'{{/macro}}
			$report->raw_query = preg_replace('/([^\{])\{'.$params['name'].'\}([^\}])/','$1{{#'.$params['name'].'}}{{^first}},{{/first}}\'{{{value}}}\'{{/'.$params['name'].'}}$2',$report->raw_query);
		
			//allow support for {(macro)} instead of {{#macro}}{{^first}},{{/first}}('{{{value}}}'){{/macro}}
			//this is shorthand for quoted, comma separated lists
			$report->raw_query = preg_replace('/([^\{])\{\('.$params['name'].'\)\}([^\}])/','$1{{#'.$params['name'].'}}{{^first}},{{/first}}(\'{{{value}}}\'){{/'.$params['name'].'}}$2',$report->raw_query);
		}
		//macros sortcuts for non-arrays
		else {
			//allow support for {macro} instead of {{{macro}}} for legacy support
			$report->raw_query = preg_replace('/([^\{])(\{'.$params['name'].'+\})([^\}])/','$1{{$2}}$3',$report->raw_query);
		}
		
		//if the macro value is empty and empty isn't allowed
		//mark the report as not ready to stop it being run
		if(trim($report->macros[$params['name']])==='' && (!isset($params['empty']) || !$params['empty'])) {
			$report->is_ready =false;
		}
	}
	
	public static function parseShortcut($value) {
		list($var,$params) = explode(',',$value,2);
		$var = trim($var);
		$params = trim($params);
		
		$parts = explode(',',$params);
		$params = array(
			'name'=>$var,
			'display'=>$parts[0]
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
				
				//name, Table.Column style
				if(preg_match('/[^\|\.]*\.[^\|\.]*/',$parts[1])) {
					list($table,$column) = explode('.',$parts[1],2);
					$var_params = array(
						'table'=>$table,
						'column'=>$column
					);
					
					//name, Table.Column, ALL style
					if(isset($parts[2]) && trim($parts[2])==='ALL') {
						$var_params['all'] = true;
					}
					//name, Table.Column, Where[, ALL] style
					elseif(isset($parts[2])) {
						$var_params['where'] = $parts[2];
						
						if(isset($parts[3]) && trim($parts[3])==='ALL') {
							$var_params['all'] = true;
						}
					}
					
					$params['database_options'] = $var_params;
				}
				else {
					$params['options'] = explode('|',$parts[1]);
				}
			}
		}
		
		return $params;
	}
	
	public static function afterParse(&$report) {
		$classname = $report->options['Type'].'ReportType';
		
		foreach($report->options['Includes'] as $included_report) {
			$report->importHeaders($included_report,'Variable');
		}
		
		foreach($report->options['Variables'] as $var=>$params) {
			//if it's a select variable and the options are pulled from a database
			if(isset($params['database_options'])) {
				$classname::openConnection($report);
				$params['options'] = $classname::getVariableOptions($params['database_options'],$report);
				
				$report->options['Variables'][$var] = $params;
			}
		}
	}
	
	public static function beforeRun(&$report) {
		foreach($report->options['Variables'] as $var=>$params) {
			//if the type is date, parse with strtotime
			if($params['type'] === 'date' && $report->macros[$params['name']]) {
				$time = strtotime($report->macros[$params['name']]);
				if(!$time) throw new Exception($params['display']." must be a valid datetime value.");
				
				$report->macros[$params['name']] = date($params['format'],$time);
			}
		}
	}
}
