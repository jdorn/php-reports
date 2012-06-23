<?php
class OptionsHeader extends HeaderBase {
	static $validation = array(
		'limit'=>array(
			'type'=>'number',
			'default'=>null
		),
		'access'=>array(
			'type'=>'enum',
			'values'=>array('rw','readonly'),
			'default'=>'readonly'
		),
		'noborder'=>array(
			'type'=>boolean,
			'default'=>false
		),
		'noreport'=>array(
			'type'=>boolean,
			'default'=>false
		),
		'vertical'=>array(
			'type'=>boolean,
			'default'=>false
		)
	);
	
	public static function init($params, &$report) {
		foreach($params as $key=>$value) {
			$report->options[$key] = $value;
		}
	}
	
	public static function parseShortcut($value) {
		$options = explode(',',$value);
		
		$params = array();
		
		foreach($options as $v) {
			if(strpos($v,'=')!==false) {
				list($k,$v) = explode('=',$v,2);
				$v = trim($v);
			}
			else {
				$k = $v;
				$v=true;
			}
			
			$k = trim($k);
			
			$params[$k] = $v;
		}
		
		return $params;
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
