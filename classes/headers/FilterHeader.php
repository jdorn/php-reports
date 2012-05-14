<?php
class FilterHeader extends HeaderBase {
	//in format: column, params
	//params can be a JSON object or "filter"
	//filter classes are defined in class/filters/
	//examples:
	//	"4,geoip" - apply a geoip filter to the 4th column
	//	'Ip,{"filter":"geoip"}' - apply a geoip filter to the "Ip" column
	public static function parse($key, $value, &$report) {		
		if(strpos($value,',') === false) {
			$col = 1;
			$params = $value;
		}
		else {
			list($col,$params) = explode(',',$value,2);
			$col = trim($col);
		}
		$params = trim($params);
		
		//JSON format
		if($temp = json_decode($params,true)) {
			$params = $temp;
		}
		//just filter name
		else {
			$params = array(
				'filter'=>$params
			);
		}
		
		if(!class_exists($params['filter'].'Filter')) {
			throw new Exception("Unknown filter '$params[filter]' in ".$report->report);
		}
		
		if(!isset($report->options['Filters'])) $report->options['Filters'] = array();
		$report->options['Filters'][$col] = $params;
	}
	
	public static function filterRow($row, &$report) {
		$i = 1;
		foreach($row['values'] as $key=>$value) {
			//get filter fot column
			if(isset($report->options['Filters'][$value['key']])) {
				$filter = $report->options['Filters'][$value['key']]['filter'];
			}
			elseif(isset($report->options['Filters'][$i]['filter'])) {
				$filter = $report->options['Filters'][$i]['filter'];
			}
			else {
				$filter = false;
			}			
			$i++;
			
			if(!$filter) continue;
			
			$classname = $filter.'Filter';
			if(class_exists($classname)) {
				$row['values'][$key]['value'] = $classname::filter($value['key'],$value['value']);
			}
		}
		
		return $row;
	}
}
