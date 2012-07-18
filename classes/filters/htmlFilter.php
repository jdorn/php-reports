<?php
class htmlFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		$value->is_html = true;
		return $value;
	}
}
