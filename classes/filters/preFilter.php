<?php
class preFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		$value['value'] = '<pre>'.$value['value'].'</pre>';
		$value['raw'] = true;
		
		return $value;
	}
}
