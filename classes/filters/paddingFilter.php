<?php
class paddingFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		$value['value'] = htmlentities($value['value']);
		$value['raw'] = true;
		
		if($options['direction'] === 'r') {
			$value['value'] .= str_repeat('&nbsp;',$options['spaces']);
			$value['class'] = 'right';
		}
		elseif($options['direction'] === 'l') {
			$value['value'] = str_repeat('&nbsp;',$options['spaces']).$value['value'];
		}
		
		return $value;
	}
}
