<?php
class StatusHeader extends InfoHeader {	
	public static function init($params, &$report) {
		trigger_error("STATUS header is deprecated.  Use the INFO header with the 'status' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'status'=>$value
		);
	}
}
