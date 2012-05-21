<?php
class hideFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		return false;
	}
}
