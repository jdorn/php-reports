<?php
class ChartHeader implements HeaderInterface {
	public static $allowed_options = array(
		'x'=>true,
		'y'=>true,
		'omit-totals'=>true
	);
	
	//in format: params
	//params can be a JSON object or key=value,key=value format
	//	'x' - the column to use for the x axis
	//	'y' - the column(s) to use for the y axis (array or ':' separated list)
	//	'omit-totals' - if set to true, will not include total rows
	public static function parse($key, $value, &$report) {
		//chart parameters in JSON format
		if($temp = json_decode($value,true)) {
			$value = $temp;
		}
		//chart parameters in key=value,key2=value2 format
		else {
			$params = explode(',',$value);
			$value = array();
			foreach($params as $param) {
				$param = trim($param);
				if(strpos($param,'=') !== false) {							
					list($key,$val) = explode('=',$param,2);
					$key = trim($key);
					$val = trim($val);
					
					if($key === 'y' || $key === 'x') {
						$val = explode(':',$val);
					}
				}
				else {
					$key = $param;
					$val = true;
				}
				
				$value[$key] = $val;
			}
		}
		
		if($temp = array_diff($value, self::$allowed_options)) {
			throw new Exception("Unknown options: ".print_r($temp,true));
		}
		
		$report->options['Chart'] = $value;
	}
}
