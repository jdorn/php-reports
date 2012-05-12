<?php
class DescriptionHeader implements HeaderInterface {
	public static function parse($key, $value, &$report) {
		$report->options['Description'] = $value;
	}
}
