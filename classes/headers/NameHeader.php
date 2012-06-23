<?php
class NameHeader extends HeaderBase {
	static $validation = array(
		'name'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Name'] = $params['name'];
	}
	
	public static function parseShortcut($value) {		
		return array(
			'name'=>$value
		);
	}
}
