<?php
class OptionsHeader extends HeaderBase {
	protected static $allowed_options = array(
		'limit'=>true,
		'access'=>true,
		'noborder'=>true,
		'noreport'=>true,
		'vertical'=>true
	);
	
	public static function parse($key, $value, &$report) {
		$options = explode(',',$value);
		
		foreach($options as $v) {
			if(strpos($v,'=')!==false) {
				list($k,$v) = explode('=',$v,2);
			}
			else {
				$k = $v;
			}
			
			$k = trim($k);
			$v = trim($v);
			
			if(!in_array($k,self::$allowed_options)) {
				throw new Exception("Unknown OPTION '$k'");
			}
			
			$report->options[$k] = $v;
		}
	}
	
	public static function beforeRender(&$report) {
		if(isset($report->options['limit'])) {
			$report->options['Rows'] = array_slice($report->options['Rows'],0,intval($report->options['limit']));
		}
		
		if(isset($report->options['vertical'])) {
			$rows = array();
			foreach($report->options['Rows'] as $row) {
				foreach($row['values'] as $value) {
					if(!isset($rows[$value['key']])) {
						$rows[$value['key']] = array(
							'values'=>array(
								array(
									'value'=>$value['key'],
									'raw_value'=>$value['key'],
									'is_header'=>true,
									'class'=>isset($value['class'])? $value['class'] : '',
									'first'=>true
								)
							),
							'first'=>!$rows
						);
					}
					
					$value['first'] = false;
					$rows[$value['key']]['values'][] = $value;
				}
			}
			
			$rows = array_values($rows);
			
			$report->options['VerticalRows'] = $rows;
		}
	}
}
