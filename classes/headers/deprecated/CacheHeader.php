<?php
class CacheHeader extends OptionsHeader {	
	public static function init($params, &$report) {
		trigger_error("CACHE header is deprecated.  Use the OPTIONS header with the 'cache' parameter instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
	
	public static function parseShortcut($value) {
		//if a cache ttl is being set
		if(is_numeric($value)) {
			return array(
				'cache'=>intval($value)
			);
		}
		//if cache is being turned off
		else {
			return array(
				'cache'=>0
			);
		}
	}
}
