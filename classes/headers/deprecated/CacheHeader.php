<?php
class CacheHeader extends OptionsHeader {	
	public static function parseShortcut($value) {
		//if a cache ttl is being set
		if(is_numeric($value)) {
			return array(
				'Cache'=>intval($value)
			);
		}
		//if cache is being turned off
		else {
			return array(
				'Cache'=>0
			);
		}
	}
}
