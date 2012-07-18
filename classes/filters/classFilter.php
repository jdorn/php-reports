<?php
class classFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		$value->addClass($options['class']);
		
		return $value;
	}
}
