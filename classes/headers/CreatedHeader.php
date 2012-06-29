<?php
class CreatedHeader extends HeaderBase {
	static $validation = array(
		'date'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Type'] = $params['type'];
	}
	
	public static function parseShortcut($value) {
		return array(
			'date'=>$value
		);
	}
}
