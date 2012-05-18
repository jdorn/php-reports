<?php
class preFilter extends FilterBase {	
	public static function filter($value, $options = array()) {
		$value['value'] = '<pre>'.$value['value'].'</pre>';
		$value['raw'] = true;
		
		return $value;
	}
}
