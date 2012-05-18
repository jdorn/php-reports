<?php
class geoipFilter extends FilterBase {	
	public static function filter($value, $options = array()) {
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
