<?php
class NoteHeader extends InfoHeader {
	public static function init($params, &$report) {
		trigger_error("NOTE header is deprecated.  Use the INFO header with the 'note' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		return array(
			'note'=>$value
		);
	}
}
