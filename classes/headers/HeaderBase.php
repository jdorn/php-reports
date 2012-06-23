<?php
class HeaderBase {
	static $validation = array();
	
	public static function parse($key, $value, &$report) {		
		
		//try to json_decode value
		$params = json_decode($value, true);
		if(!$params) $params = static::parseShortcut($value);
		
		if(!$params) throw new Exception("Could not parse header $key");
		
		$params = static::validate($params);
		
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
	
	protected static function validate($params) {
		if(!static::$validation) return $params;
		
		$errors = array();
		
		foreach(static::$validation as $key=>$rules) {
			if(isset($rules['default']) && !isset($params[$key])) {
				$params[$key] = $rules['default'];
				continue;
			}
			
			if((!isset($rules['required']) || !$rules['required']) && !isset($params[$key])) continue;
			
			if(isset($rules['type'])) {
				if($rules['type'] === 'number' && !is_numeric($params[$key])) $errors[] = "$key must be a number";
				elseif($rules['type'] === 'array' && !is_array($params[$key])) $errors[] = "$key must be an array";
				elseif($rules['type'] === 'boolean' && !is_bool($params[$key])) $errors[] = "$key must be true or false";
				elseif($rules['type'] === 'string' && !is_string($params[$key])) $errors[] = "$key must be a string";
				elseif($rules['type'] === 'enum' && !in_array($params[$key],$rules['values'])) $errors[] = "$key must be one of: [".implode(', ',$rules['values'])."]";
				elseif($rules['type'] === 'object' && !is_array($params[$key])) $errors[] = "$key must be an object";
			}
			
			if(isset($rules['min']) && $params[$key] < $rules['min']) $errors[] = "$key must be at least $rules[min]";
			if(isset($rules['max']) && $params[$key] > $rules['max']) $errors[] = "$key must be at most $rules[min]";
		}
				
		foreach($params as $k=>$v) {
			if(!isset(static::$validation[$k])) $errors[] = "Unknown parameter $k for $key header";
		}
		
		if($errors) {
			throw new Exception(implode(". ",$errors));
		}
		else return $params;
	}
}
