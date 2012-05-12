<?php
class ColumnsHeader implements HeaderInterface {
	public static function parse($key, $value, &$report) {
		if($temp = json_decode($value,true)) {
			$value = $temp;
		}
		else {
			$value = explode(',',$value);
		}
		
		$report->options['Columns'] = $value;
	}
}
