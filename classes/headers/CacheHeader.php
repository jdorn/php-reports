<?php
class CacheHeader extends TotalsHeader {
	static $validation = array(
		'ttl'=>array(
			'min'=>0,
			'type'=>'number',
			'required'=>true
		)
	);
	
	public static function init($params, &$report) {
		$report->options['Cache'] = intval($params['ttl']);
	}
	
	public static function parseShortcut($value) {
		//if a cache ttl is being set
		if(is_numeric($value)) {
			return array(
				'ttl'=>$value
			);
		}
		//if cache is being turned off
		else {
			return array(
				'ttl'=>0
			);
		}
	}
}
