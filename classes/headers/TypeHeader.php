<?php
class TypeHeader implements HeaderInterface {
	public static function parse($key, $value, &$report) {
		$report->options['Type'] = $value;
	}
}
