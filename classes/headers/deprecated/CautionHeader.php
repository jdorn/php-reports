<?php
class CautionHeader extends HeaderBase {
	static $validation = array(
		'value'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		trigger_error("CAUTION header is deprecated.",E_USER_DEPRECATED);
		
		$report->options['Caution'] = $params['value'];
			
		$report->exportHeader('Caution',$params);
	}
	
	public static function parseShortcut($value) {
		return array(
			'value'=>$value
		);
	}
}
