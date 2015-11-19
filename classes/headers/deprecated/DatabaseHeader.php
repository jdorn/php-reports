<?php
class DatabaseHeader extends OptionsHeader {
	public static function init($params, &$report) {
		trigger_error("DATABASE header is deprecated.  Use the OPTIONS header with the 'database' parameter instead. (".$report->report.")",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'database'=>trim($value)
		);
	}
}
