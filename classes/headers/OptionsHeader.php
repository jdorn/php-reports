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
			'type'=>'boolean',
			'default'=>false
		),
		'noreport'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'vertical'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'ignore'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'table'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'showcount'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'font'=>array(
			'type'=>'string'
		),
		'stop'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'nodata'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'version'=>array(
			'type'=>'number',
			'default'=>1
		),
		'selectable'=>array(
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		foreach($params as $key=>$value) {
			$report->options[$key] = $value;
			
			//if the value is different from the default, export it
			if($value && $value !== self::$validation[$key]['default']) {
				//export the acceess option if defined
				//all other options should not be exported
				if($key === 'access') {
					$report->exportHeader('Options',array($key=>$value));
				}
			}
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
		
		if(isset($report->options['selectable']) && isset($_REQUEST['selected'])) {			
			$selected_key = null;
			foreach($report->options['Rows'][0]['values'] as $key=>$value) {
				if($value->key == $report->options['selectable']) {
					$selected_key = $key;
					break;
				}
			}
			
			if($selected_key !== null) {
				foreach($report->options['Rows'] as $key=>$row) {
					
					if(!in_array($row['values'][$selected_key]->getValue(),$_GET['selected'])) {
						unset($report->options['Rows'][$key]);
					}
				}
				$report->options['Rows'] = array_values($report->options['Rows']);
			}
		}
		
		if(isset($report->options['vertical'])) {
			$rows = array();
			foreach($report->options['Rows'] as $row) {
				foreach($row['values'] as $value) {
					if(!isset($rows[$value->key])) {
						$header = new ReportValue(1, 'key', $value->key);
						$header->class = 'left lpad';
						$header->is_header = true;
						
						$rows[$value->key] = array(
							'values'=>array(
								$header
							),
							'first'=>!$rows
						);
					}
					
					$rows[$value->key]['values'][] = $value;
				}
			}
			
			$rows = array_values($rows);
			
			$report->options['VerticalRows'] = $rows;
		}
	}
}