<?php
class NameHeader implements HeaderInterface {
	public static function parse($key, $value, &$report) {
		$report->options['Name'] = $value;
	}
}
