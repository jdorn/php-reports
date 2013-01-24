<?php
class preFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		$value->setValue("<pre>".$value->getValue(true)."</pre>",true);
		
		return $value;
	}
}
