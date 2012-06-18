<?php
class CacheHeader extends TotalsHeader {
	public static function parse($key, $value, &$report) {
		//if a cache ttl is being set
		if(is_numeric($value)) {
			if($report->use_cache !== false) {
				$report->use_cache = is_numeric($value);
			}
		}
		//if cache is being turned off
		else {
			$report->use_cache = true;
		}
		
		$report->options[$key] = $value;
	}
}
