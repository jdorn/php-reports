<?php
class TotalsHeader extends HeaderBase {
	static $validation = array(
		'value'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		trigger_error("TOTALS header is deprecated.  Use the ROLLUP header instead.",E_USER_DEPRECATED);
	}
	
	public static function parseShortcut($value) {
		return array(
			'value'=>$value
		);
	}
}
