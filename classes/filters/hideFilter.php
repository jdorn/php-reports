<?php
class hideFilter extends FilterBase {	
	public static function filter($value, $options = array()) {
		return false;
	}
}
