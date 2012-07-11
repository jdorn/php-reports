<?php
class DatabaseHeader extends HeaderBase {
	static $validation = array(
		'database'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Database'] = $params['database'];
	}
	
	public static function parseShortcut($value) {
		return array(
			'database'=>trim($value)
		);
	}
}
