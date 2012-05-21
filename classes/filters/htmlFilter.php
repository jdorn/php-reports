<?php
class htmlFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		//don't escape value
		$value['raw'] = true;
		return $value;
	}
}
