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
		),
		'mongodatabase'=>array(
			'type'=>'string'
		),
		'database'=>array(
			'type'=>'string'
		),
		'cache'=>array(
			'min'=>0,
			'type'=>'number'
		),
		'ttl'=>array(
			'min'=>0,
			'type'=>'number'
		),
		'default_dataset'=>array(
			'type'=>'number',
			'default'=>0
		),
		'has_charts'=>array(
			'type'=>'boolean'
		)
	);
	
	public static function init($params, &$report) {
		//legacy support for the 'ttl' cache parameter
		if(isset($params['ttl'])) {
			$params['cache'] = $params['ttl'];
			unset($params['ttl']);
		}

		if(isset($params['has_charts']) && $params['has_charts']) {
			if(!isset($report->options['Charts'])) $report->options['Charts'] = array();
		}
		
		// Some parameters were moved to a 'FORMATTING' header
		// We need to catch those and add the header to the report
		$formatting_header = array();
		
		foreach($params as $key=>$value) {
			// This is a FORMATTING parameter
			if(in_array($key,array('limit','noborder','vertical','table','showcount','font','nodata','selectable'))) {
				$formatting_header[$key] = $value;
				continue;
			}
			
			//some of the keys need to be uppercase (for legacy reasons)
			if(in_array($key,array('database','mongodatabase','cache'))) $key = ucfirst($key);
			
			$report->options[$key] = $value;
			
			//if the value is different from the default, it can be exported
			if($value && $value !== self::$validation[$key]['default']) {
				//only export some of the options
				if(in_array($key,array('access','Cache'),true)) {
					$report->exportHeader('Options',array($key=>$value));
				}
			}
		}
		
		if($formatting_header) {
			$formatting_header['dataset'] = true;
			$report->parseHeader('Formatting',$formatting_header);
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
}
