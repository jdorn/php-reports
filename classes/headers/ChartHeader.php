<?php
class ChartHeader extends HeaderBase {
	public static $allowed_options = array(
		'x'=>true,
		'y'=>true,
		'omit-totals'=>true,
		'type'=>true
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
		
		if($temp = array_diff_key($value, self::$allowed_options)) {
			throw new Exception("Unknown options: ".print_r($temp,true));
		}
		
		if(!isset($value['type'])) {
			$value['type'] = 'LineChart';
		}
		
		$report->options['Chart'] = $value;
		
		$report->options['ChartRows'] = array();
	}
	
	public static function filterRow($row, &$report) {
		if($row['first']) return $row;
	
		//if this is a total row and we're omitting totals from charts
		if(isset($report->options['Chart']['omit-totals']) 
			&& $report->options['Chart']['omit-totals'] 
			&& strtoupper($row['values'][0]['value'])==='TOTAL'
		) {
			return $row;
		}
	
		$i = 1;
		$chartrowvals = array();
		foreach($row['values'] as $key=>$value) {
				//determine if this column should appear in a chart
				$column_in_chart = false;
				if(!isset($report->options['Chart']['y'])) {
					$column_in_chart = true;
					$x = false;
				}
				elseif(in_array($value['key'],$report->options['Chart']['y'],true) || in_array($i,$report->options['Chart']['y'],true)) {
					$column_in_chart = true;
					$x = false;
				}
				elseif($i===1 && !isset($report->options['Chart']['x'])) {
					$column_in_chart = true;
					$x = true;
				}
				elseif(isset($report->options['Chart']['x']) && ($value['key']==$report->options['Chart']['x'] || $i == $report->options['Chart']['x'])) {
					$column_in_chart = true;
					$x = true;
				
				}
				
				$i++;
				
				if(!$column_in_chart) {
					continue;
				}
				
				if($x) {
					array_unshift($chartrowvals,array(
						'key'=>$value['key'],
						'value'=>$value['value'],
						'first'=>true
					));
				}
				else {
					$chartrowvals[] = array(
						'key'=>$value['key'],
						'value'=>$value['value'],
						'first'=>false
					);
				}
		}
		
		//echo "<pre>".print_r($chartrowvals,true)."</pre>";
		
		$report->options['ChartRows'][] = array(
			'values'=>$chartrowvals,
			'first'=>!$report->options['ChartRows']
		);
		
		return $row;
	}
}
