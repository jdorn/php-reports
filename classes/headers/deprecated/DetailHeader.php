<?php
class DetailHeader extends HeaderBase {
	static $validation = array(
		'report'=>array(
			'required'=>true,
			'type'=>'string'
		),
		'column'=>array(
			'required'=>true,
			'type'=>'string'
		),
		'macros'=>array(
			'type'=>'object'
		)
	);
	
	public static function init($params, &$report) {
		trigger_error("DETAIL header is deprecated.  Use the FILTER header with the 'drilldown' filter instead.",E_USER_DEPRECATED);
		
		$report->addFilter($params['column'],'drilldown',$params);
	}
	
	public static function parseShortcut($value) {
		$parts = explode(',',$value,3);
		
		if(count($parts) < 2) {
			throw new Exception("Cannot parse DETAIL header '$value'");
		}
		
		$col = trim($parts[0]);
		$report_name = trim($parts[1]);
		
		if(isset($parts[2])) {
			$parts[2] = trim($parts[2]);
			$macros = array();
			$temp = explode(',',$parts[2]);
			foreach($temp as $macro) {
				$macro = trim($macro);
				if(strpos($macro,'=') !== false) {
					list($key,$val) = explode('=',$macro,2);
					$key = trim($key);
					$val = trim($val);
					
					if(in_array($val[0],array('"',"'"))) {
						$val = array(
							'constant'=>trim($val,'\'"')
						);
					}
					else {
						$val = array(
							'column'=>$val
						);
					}
					
					$macros[$key] = $val;
				}
				else {
					$macros[$macro] = $macro;
				}
			}
			
		}
		else {
			$macros = array();
		}
		
		return array(
			'report'=>$report_name,
			'column'=>$col,
			'macros'=>$macros
		);
	}
}
