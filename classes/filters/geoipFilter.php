<?php
class geoipFilter implements FilterInterface {
	public static function canEscape() {
		return true;
	}
	
	public static function filter($key, $value, $options = array()) {
		$record = @geoip_record_by_name($value);
		if($record) {
			$value = $record['city'];
			if($record['country_code'] !== 'US') {
				$value .= ' '.$record['country_name'];
			}
			else {
				$value .= ', '.$record['region'];
			}
		}
		
		return trim($value);
	}
}
