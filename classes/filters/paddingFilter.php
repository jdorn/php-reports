<?php
class paddingFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		
		if($options['direction'] === 'r') {
			$value->addClass('right');
		}
		elseif($options['direction'] === 'l') {
			$value->addClass('left');
		}
		
		return $value;
	}
}
