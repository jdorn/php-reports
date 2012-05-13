<?php
class NameHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		$report->options['Name'] = $value;
	}
}
