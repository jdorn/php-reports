<?php
class FormattingHeader extends HeaderBase {
	static $validation = array(
		'limit'=>array(
			'type'=>'number',
			'default'=>null
		),
		'noborder'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'vertical'=>array(
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
		'nodata'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'selectable'=>array(
			'type'=>'string'
		),
		'dataset'=>array(
			'required'=>true,
			'default'=>true
		)
	);
	
	public static function init($params, &$report) {
		if(!isset($report->options['Formatting'])) $report->options['Formatting'] = array();
		$report->options['Formatting'][] = $params;
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
		$formatting = array();
		// Expand out by dataset
		foreach($report->options['Formatting'] as $params) {
			$copy = $params;
			unset($copy['dataset']);
			
			// Multiple datasets defined
			if(is_array($params['dataset'])) {
				foreach($params['dataset'] as $i) {
					if(isset($report->options['DataSets'][$i])) {
						if(!isset($formatting[$i])) $formatting[$i] = array();
						foreach($copy as $k=>$v) {
							$formatting[$i][$k] = $v;
						}
					}
				}
			}
			// All datasets
			elseif($params['dataset']===true) {
				foreach($report->options['DataSets'] as $i=>$dataset) {
					if(!isset($formatting[$i])) $formatting[$i] = array();
					foreach($copy as $k=>$v) {
						$formatting[$i][$k] = $v;
					}
				}
			}
			// Single dataset
			else {
				if(!isset($report->options['DataSets'][$params['dataset']])) continue;
				if(!isset($formatting[$params['dataset']])) $formatting[$params['dataset']] = array();
				foreach($copy as $k=>$v) {
					$formatting[$params['dataset']][$k] = $v;
				}
			}
		}
		
		// Apply formatting options for each dataset
		foreach($formatting as $i=>$params) {
			if(isset($params['limit']) && $params['limit']) {
				$report->options['DataSets'][$i]['rows'] = array_slice($report->options['DataSets'][$i]['rows'],0,intval($params['limit']));
			}
			if(isset($params['selectable']) && $params['selectable']) {
				$selected = array();
				
				// New style "selected_{{DATASET}}" querystring
				if(isset($_GET['selected_'.$i])) {
					$selected = $_GET['selected_'.$i];
				}
				// Old style "selected" querystring
				elseif(isset($_GET['selected'])) {	
					$selected = $_GET['selected'];
				}
				
				if($selected) {
					$selected_key = null;
					foreach($report->options['DataSets'][$i]['rows'][0]['values'] as $key=>$value) {
						if($value->key == $params['selectable']) {
							$selected_key = $key;
							break;
						}
					}
			
					if($selected_key !== null) {
						foreach($report->options['DataSets'][$i]['rows'] as $key=>$row) {
							
							if(!in_array($row['values'][$selected_key]->getValue(),$selected)) {
								unset($report->options['DataSets'][$i]['rows'][$key]);
							}
						}
						$report->options['DataSets'][$i]['rows'] = array_values($report->options['DataSets'][$i]['rows']);
					}
				}
			}
			if(isset($params['vertical']) && $params['vertical']) {
				$rows = array();
				foreach($report->options['DataSets'][$i]['rows'] as $row) {
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
				
				$report->options['DataSets'][$i]['vertical'] = $rows;
			}
			
			unset($params['vertical']);
			foreach($params as $k=>$v) {
				$report->options['DataSets'][$i][$k] = $v;
			}
		}
	}
}
