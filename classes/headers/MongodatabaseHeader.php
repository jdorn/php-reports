<?php
class MongodatabaseHeader extends HeaderBase {
	static $validation = array(
		'database'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Mongodatabase'] = $params['database'];
	}
	
	public static function parseShortcut($value) {
		return array(
			'database'=>$value
		);
	}
}
