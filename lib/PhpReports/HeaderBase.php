<?php
class HeaderBase {
	static $validation = array();
	
	public static function parse($key, $value, &$report) {				
		$params = null;
		
		if(is_array($value)) {
			$params = $value;
		}
		//try to json_decode value
		//this is a wrapper json_decode function that supports non-strict JSON
		//for example, {key:"value",'key2':'value2',}
		elseif($value[0] === '{') {
			$params = PhpReports::json_decode($value, true);
		}
		
		//if it couldn't be parsed as json, try parsing it as a shortcut form
		if(!$params) $params = static::parseShortcut($value);
		
		if(!$params) throw new Exception("Could not parse header '$key'");
		
		//run defined validation rules and fill in default params
		try {
			$params = static::validate($params);
		}
		catch(Exception $e) {
			throw new Exception($key." Header: ".$e->getMessage());
		}
		
		static::init($params, $report);
	}
	
	public static function init($params, &$report) {
		
	}
	
	public static function parseShortcut($value) {
		return array();
	}
	
	public static function beforeRender(&$report) {
		
	}
	
	public static function afterParse(&$report) {
		
	}
	
	public static function beforeRun(&$report) {
		
	}
	
	protected static function validate($params) {
		if(!static::$validation) return $params;
		
		$errors = array();
		
		foreach(static::$validation as $key=>$rules) {
			//fill in default params
			if(isset($rules['default']) && !isset($params[$key])) {
				$params[$key] = $rules['default'];
				continue;
			}
			
			//if the param isn't required and it's defined, we can skip validation
			if((!isset($rules['required']) || !$rules['required']) && !isset($params[$key])) continue;
			
			//if the param must be a specific datatype
			if(isset($rules['type'])) {
				if($rules['type'] === 'number' && !is_numeric($params[$key])) $errors[] = "$key must be a number (".gettype($params[$key])." given)";
				elseif($rules['type'] === 'array' && !is_array($params[$key])) $errors[] = "$key must be an array (".gettype($params[$key])." given)";
				elseif($rules['type'] === 'boolean' && !is_bool($params[$key])) $errors[] = "$key must be true or false (".gettype($params[$key])." given)";
				elseif($rules['type'] === 'string' && !is_string($params[$key])) $errors[] = "$key must be a string (".gettype($params[$key])." given)";
				elseif($rules['type'] === 'enum' && !in_array($params[$key],$rules['values'])) $errors[] = "$key must be one of: [".implode(', ',$rules['values'])."]";
				elseif($rules['type'] === 'object' && !is_array($params[$key])) $errors[] = "$key must be an object (".gettype($params[$key])." given)";
			}
			
			//other validation rules
			if(isset($rules['min']) && $params[$key] < $rules['min']) $errors[] = "$key must be at least $rules[min]";
			if(isset($rules['max']) && $params[$key] > $rules['max']) $errors[] = "$key must be at most $rules[min]";
			
			if(isset($rules['pattern']) && !preg_match($rules['pattern'],$params[$key])) $errors[] = "$key does not match required pattern";
		}
		
		//every possible param must be defined in the validation rules
		foreach($params as $k=>$v) {
			if(!isset(static::$validation[$k])) $errors[] = "Unknown parameter '$k'";
		}
		
		if($errors) {
			throw new Exception(implode(". ",$errors));
		}
		else return $params;
	}
}
