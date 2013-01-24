<?php
class NameHeader extends InfoHeader {
	public static function init($params, &$report) {
		trigger_error("NAME header is deprecated.  Use the INFO header with the 'name' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {		
		return array(
			'name'=>$value
		);
	}
}
