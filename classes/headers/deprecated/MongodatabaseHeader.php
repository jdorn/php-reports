<?php
class MongodatabaseHeader extends OptionsHeader {
	public static function init($params, &$report) {
		trigger_error("MONGODATABASE header is deprecated.  Use the OPTIONS header with the 'Mongodatabase' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'Mongodatabase'=>$value
		);
	}
}
