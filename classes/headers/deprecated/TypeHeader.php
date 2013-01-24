<?php
class TypeHeader extends InfoHeader {
	public static function init($params, &$report) {
		trigger_error("TYPE header is deprecated.  Use the INFO header with the 'type' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'type'=>$value
		);
	}
}
