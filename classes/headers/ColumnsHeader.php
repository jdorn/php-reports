<?php
class ColumnsHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		if($temp = json_decode($value,true)) {
			$value = $temp;
		}
		else {
			$value = explode(',',$value);
		}
		
		$report->options['Columns'] = $value;
	}
	
	public static function filterRow($row, &$report) {
		$i = 0;
		//print_r($row);
		foreach($row['values'] as $key=>$value) {
			//get class for column
			if(isset($report->options['Columns'][$i])) {
				$class = $report->options['Columns'][$i];
			}
			else {
				$class = false;
			}
			if(!$class) continue;
			
			//unescaped output
			if($class === 'raw') {
				$row['values'][$key]['raw'] = true;
			}
			//output wrapped in <pre> tags
			elseif($class === 'pre') {
				$row['values'][$key]['pre'] = true;
			}
			//classname
			else {
				$row['values'][$key]['class'] = $class;
			}
			
			$i++;
		}
		
		return $row;
	}
}
