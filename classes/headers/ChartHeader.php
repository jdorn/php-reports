<?php
class ChartHeader extends HeaderBase {
	public static $allowed_options = array(
		'x'=>true,
		'y'=>true,
		'omit-total'=>true,
		'rotate-x-labels'=>false,
		'type'=>true,
		'buckets'=>true,
		'title'=>true,
		'width'=>true,
		'height'=>true,
		'grid'=>false,
		'timefmt'=>false,
		'xformat'=>false,
		'yrange'=>false,
		'xhistogram'=>false
	);
	
	//in format: params
	//params can be a JSON object or key=value,key=value format
	//	'x' - the column to use for the x axis
	//	'y' - the column(s) to use for the y axis (array or ':' separated list)
	//	'omit-total' - if set to true, will not include total rows
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
			throw new Exception("Unknown chart options ($report->report): ".print_r($temp,true));
		}
		
		if(!isset($value['type'])) {
			$value['type'] = 'LineChart';
		}
		
		if(!isset($report->options['Charts'])) $report->options['Charts'] = array();
		
		$value['num'] = count($report->options['Charts'])+1;
		$value['Rows'] = array();
		
		$report->options['Charts'][] = $value;
		
		$report->options['has_charts'] = true;
	}
	
	//for basic graphs where there is a 1 to 1 relationship between values and data points
	public static function filterRowTwoDim($num, $row, &$report) {
	
		$is_total_row = false;
		if(trim(strtoupper($row['values'][0]['raw_value']))==='TOTAL') $is_total_row = true;
	
		//if this is a total row and we're omitting totals from charts
		if(isset($report->options['Charts'][$num]['omit-total']) 
			&& $report->options['Charts'][$num]['omit-total'] 
			&& $is_total_row
		) {
			return;
		}
	
		$i = 1;
		$chartrowvals = array();
		foreach($row['values'] as $key=>$value) {			
			//determine if this column should appear in a chart
			$column_in_chart = false;
			$x = false;
			if(!isset($report->options['Charts'][$num]['y'])) {
				$column_in_chart = true;
			}
			elseif(in_array($value['key'],$report->options['Charts'][$num]['y'],true) 
				|| in_array("$i",$report->options['Charts'][$num]['y'],true)
			) {
				$column_in_chart = true;
			}
			elseif(!$xval && $i===1 && !isset($report->options['Charts'][$num]['x'])) {
				$column_in_chart = true;
				$x = true;
			}
			elseif(!$xval && isset($report->options['Charts'][$num]['x']) 
				&& (
					$value['key']==$report->options['Charts'][$num]['x'] 
					|| "$i" === "".$report->options['Charts'][$num]['x']
				)
			) {
				$column_in_chart = true;
				$x = true;
			}
			
			$i++;
			
			if(!$column_in_chart) {
				continue;
			}
			
			if($x) {
				$xval = array(
					'key'=>$value['key'],
					'value'=>$value['value'],
					'first'=>true
				);
			}
			else {
				$chartrowvals[] = array(
					'key'=>$value['key'],
					'value'=>floatval($value['value']),
					'first'=>false
				);
			}
		}
		
		array_unshift($chartrowvals,$xval);
		
		//echo "<pre>".print_r($chartrowvals,true)."</pre>";
		$first = count($report->options['Charts'][$num]['Rows']);
		
		$report->options['Charts'][$num]['Rows'][] = array(
			'values'=>$chartrowvals,
			'first'=>!$first
		);	
	}
	
	//used for histogram or box plot
	//values are grouped into buckets
	public static function filterRowOneDim($num, $row, &$report) {
		if(!$report->options['Charts'][$num]['Rows']) {
			$i = 1;
			foreach($report->options['Values'] as $key=>$values) {
				if(isset($report->options['Charts'][$num]['x'])
					&& in_array($report->options['Charts'][$num]['x'],array($i,$key),true)
				) {
					$col = $key;
					break;
				}
				elseif(!isset($report->options['Charts'][$num]['x']) && $values) {
					$col = $key;
					break;
				}
				$i++;
			}
			
			$max = max($values);
			$min = min($values);
			$step = ($max-$min)/$report->options['Charts'][$num]['buckets'];
			
			for($i = $min; $i< $max; $i += $step) {
				$report->options['Charts'][$num]['Rows'][] = array(
					"first"=>!$report->options['Charts'][$num]['Rows'],
					"values"=>array(
						array(
							"key"=>"Bucket",
							"value"=>round($i,2).' - '.round($i+$step,2),
							"first"=>true
						),
						array(
							"key"=>"Number",
							"first"=>false,
							"value"=>count(array_filter($values,function($a) use($i,$step) {
								if($a>$i && $a <= ($i+$step)) return true;
								else return false;
							}))
						)
					)
				);
			}
		}
		return;
	}
	
	public static function filterRow($row, &$report) {
		if($row['first']) return;
		
		foreach($report->options['Charts'] as $num=>$value) {
			if(isset($report->options['Charts'][$num]['buckets'])) {
				self::filterRowOneDim($num, $row, $report);
			}
			else {
				self::filterRowTwoDim($num, $row, $report);
			}
		}
		
		return;
	}
	
	public static function beforeRender(&$report) {
		foreach($report->options['Rows'] as $key=>$row) {
			self::filterRow($row,$report);
		}
	}
}
