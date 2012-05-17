<?php
class HeaderBase {
	public static function parse($key, $value, &$report) {		
		$report->options[$key] = $value;
	}
	
	public static function filterRow($row, &$report) {
		return $row;
	}
}
