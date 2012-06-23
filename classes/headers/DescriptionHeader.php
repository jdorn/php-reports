<?php
class DescriptionHeader extends HeaderBase {
	static $validation = array(
		'description'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Description'] = $params['description'];
	}
	
	public static function parseShortcut($value) {
		return array(
			'description'=>$value
		);
	}
}
