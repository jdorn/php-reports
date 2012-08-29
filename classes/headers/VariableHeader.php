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
		),
		'modifier_options'=>array(
			'type'=>'array'
		),
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
			$report->addMacro($params['name'],'');
		}
		
		//check if variable is empty
		$empty = (is_array($report->macros[$params['name']]) && !$report->macros[$params['name']]) || trim($report->macros[$params['name']])==='';
		
		//convert newline separated strings to array for vars that support multiple values
		if($params['multiple'] && !$empty && !is_array($report->macros[$params['name']])) $report->addMacro($params['name'],explode("\n",$report->macros[$params['name']]));
		
		//if the macro value is empty and empty isn't allowed
		//mark the report as not ready to stop it being run
		if($empty && (!isset($params['empty']) || !$params['empty'])) {
			$report->is_ready =false;
		}
		
		$report->exportHeader('Variable',$params);
	}
	
	public static function parseShortcut($value) {
		list($var,$params) = explode(',',$value,2);
		$var = trim($var);
		$params = trim($params);
		
		$parts = explode(',',$params);
		$params = array(
			'name'=>$var,
			'display'=>trim($parts[0])
		);
		
		unset($parts[0]);
		
		$extra = implode(',',$parts);
		
		//just "name, label"
		if(!$extra) return $params;
		
		//if the 3rd item is "LIST", use multi-select
		if(preg_match('/^\s*LIST\s*,/',$extra)) {
			$params['multiple'] = true;
			$extra = array_pop(explode(',',$extra,2));
		}
		
		//table.column, where clause, ALL
		if(preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,[^,]+,\s*ALL\s*$/', $extra)) {
			list($table_column, $where, $all) = explode(',',$extra, 3);
			list($table,$column) = explode('.',$table_column,2);
			
			$params['type'] = 'select';
			$params['multiple'] = true;
			
			$var_params = array(
				'table'=>$table,
				'column'=>$column,
				'all'=>true,
				'where'=>$where
			);
			
			$params['database_options'] = $var_params;
		}
		
		//table.column, ALL
		elseif(preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,\s*ALL\s*$/', $extra)) {
			list($table_column, $all) = explode(',',$extra, 2);
			list($table,$column) = explode('.',$table_column,2);
			
			$params['type'] = 'select';
			$params['multiple'] = true;
			
			$var_params = array(
				'table'=>$table,
				'column'=>$column,
				'all'=>true
			);
			
			$params['database_options'] = $var_params;
		}
		
		//table.column, where clause
		elseif(preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,[^,]+$/', $extra)) {
			list($table_column, $where) = explode(',',$extra, 2);
			list($table,$column) = explode('.',$table_column,2);
			
			$params['type'] = 'select';
			$params['multiple'] = true;
			
			$var_params = array(
				'table'=>$table,
				'column'=>$column,
				'where'=>$where
			);
			
			$params['database_options'] = $var_params;
		}
		
		//table.column
		elseif(preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*$/', $extra)) {
			list($table,$column) = explode('.',$extra,2);
			
			$params['type'] = 'select';
			
			$var_params = array(
				'table'=>$table,
				'column'=>$column
			);
			
			$params['database_options'] = $var_params;
		}
		
		//option1|option2
		elseif(preg_match('/^\s*([a-zA-Z0-9_\- ]+\|)+[a-zA-Z0-9_\- ]+$/',$extra)) {
			$options = explode('|',$extra);
			
			$params['type'] = 'select';
			$params['multiple'] = false;					
			$params['options'] = $options;
		}
		
		return $params;
	}
	
	public static function afterParse(&$report) {
		$classname = $report->options['Type'].'ReportType';
		
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
