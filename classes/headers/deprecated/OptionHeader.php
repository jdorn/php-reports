<?php
class OptionHeader extends OptionsHeader {
	public static function init($params, &$report) {
		trigger_error("OPTION header is deprecated.  Use the OPTIONS header instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
}
