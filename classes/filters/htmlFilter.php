<?php
class htmlFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		$value->is_html = true;
		return $value;
	}
}
