<?php
class TypeHeader extends HeaderBase {
	static $validation = array(
		'type'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Type'] = $params['type'];
	}
	
	public static function parseShortcut($value) {
		return array(
			'type'=>$value
		);
	}
}
