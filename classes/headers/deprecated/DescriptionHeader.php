<?php
class DescriptionHeader extends InfoHeader {
	public static function init($params, &$report) {
		trigger_error("DESCRIPTION header is deprecated.  Use the INFO header with the 'description' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'description'=>$value
		);
	}
}
