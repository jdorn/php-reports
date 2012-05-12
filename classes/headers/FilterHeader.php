<?php
class FilterHeader implements HeaderInterface {
	//in format: column, params
	//params can be a JSON object or "filter"
	//filter classes are defined in class/filters/
	//examples:
	//	"4,geoip" - apply a geoip filter to the 4th column
	//	'Ip,{"filter":"geoip"}' - apply a geoip filter to the "Ip" column
	public static function parse($key, $value, &$report) {
		if(strpos($value,',') === false) {
			throw new Exception("Could not parse Filter header: $value");
		}
		
		list($col,$params) = explode(',',$value,2);
		$col = trim($col);
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
		
		if(!isset($report->options['Filters'])) $report->options['Filters'] = array();
		$report->options['Filters'][$col] = $params;
	}
}
