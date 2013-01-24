<?php
class InfoHeader extends HeaderBase {
	static $validation = array(
		'name'=>array(
			'type'=>'string'
		),
		'description'=>array(
			'type'=>'string'
		),
		'created'=>array(
			'type'=>'string',
			'pattern'=>'/^[0-9]{4}-[0-9]{2}-[0-9]{2}/'
		),
		'note'=>array(
			'type'=>'string'
		),
		'type'=>array(
			'type'=>'string'
		),
		'status'=>array(
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		foreach($params as $key=>$value) {
			$report->options[ucfirst($key)] = $value;
		}
	}
	
	// Accepts shortcut format:
	// name=My Report,description=This is My Report
	public static function parseShortcut($value) {
		$parts = explode(',',$value);
		
		$params = array();
		
		foreach($parts as $v) {
			if(strpos($v,'=')!==false) {
				list($k,$v) = explode('=',$v,2);
				$v = trim($v);
			}
			else {
				$k = $v;
				$v=true;
			}
			
			$k = trim($k);
			
			$params[$k] = $v;
		}
		
		return $params;
	}
}
