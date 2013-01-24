<?php
class CreatedHeader extends InfoHeader {
	public static function init($params, &$report) {
		trigger_error("CREATED header is deprecated.  Use the INFO header with the 'created' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'created'=>$value
		);
	}
}
