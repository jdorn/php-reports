<?php
class htmlFilter extends FilterBase {	
	public static function filter($value, $options = array()) {
		//don't escape value
		$value['raw'] = true;
		return $value;
	}
}
