<?php
class ChartHeader extends HeaderBase {
	static $validation = array(
		'columns'=>array(
			'type'=>'array',
			'default'=>array()
		),
		'type'=>array(
			'type'=>'enum',
			'values'=>array('LineChart','GeoChart','AnnotatedTimeLine','BarChart','ColumnChart'),
			'default'=>'LineChart'
		),
		'title'=>array(
			'type'=>'string',
			'default'=>''
		),
		'width'=>array(
			'type'=>'string',
			'default'=>'100%'
		),
		'height'=>array(
			'type'=>'string',
			'default'=>'400px'
		),
		'xhistogram'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'buckets'=>array(
			'type'=>'number',
			'default'=>0
		),
		'omit-totals'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'omit-total'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'rotate-x-labels'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'grid'=>array(
			'type'=>'boolean',
			'default'=>false
		),
		'timefmt'=>array(
			'type'=>'string',
			'default'=>''
		),
		'xformat'=>array(
			'type'=>'string',
			'default'=>''
		),
		'yrange'=>array(
			'type'=>'string',
			'default'=>''
		),
		'all'=>array(
			'type'=>'boolean',
			'default'=>false
		),
	);
	
	public static function init($params, &$report) {
		if(!isset($params['type'])) {
			$params['type'] = 'LineChart';
		}
		
		if(isset($params['omit-total'])) {
			$params['omit-totals'] = $params['omit-total'];
			unset($params['omit-total']);
		}
		
		if(!isset($report->options['Charts'])) $report->options['Charts'] = array();
		
		if(isset($params['width'])) $params['width'] = self::fixDimension($params['width']);
		if(isset($params['height'])) $params['height'] = self::fixDimension($params['height']);
		
		$params['num'] = count($report->options['Charts'])+1;
		$params['Rows'] = array();
		
		$report->options['Charts'][] = $params;
		
		$report->options['has_charts'] = true;
	}
	protected static function fixDimension($dim) {		
		if(preg_match('/^[0-9]+$/',$dim)) $dim .= "px";
		return $dim;
	}
	
	public static function parseShortcut($value) {
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
		
		if(isset($value['x'])) $value['columns'] = $value['x'];
		else $value['columns'] = array(1);
		
		if(isset($value['y'])) $value['columns'] = array_merge($value['columns'],$value['y']);
		else $value['all'] = true;
		
		unset($value['x']);
		unset($value['y']);
		
		return $value;
	}
	
	protected static function getRowInfo($rows, $params, $num, &$report) {
		$cols = array();
		
		//expand columns
		$chart_rows = array();
		foreach($rows as $k=>$row) {
			$vals = array();			
			
			if($k===0) {
				$i=1;
				$unsorted = 1000;
				foreach($row['values'] as $key=>$value) {
					$row['values'][$key]['i'] = $i;
					$i++;
					
					if(($temp = array_search($row['values'][$key]['i'], $report->options['Charts'][$num]['columns']))!==false) {
						$cols[$temp] = $key;
					}
					elseif(($temp = array_search($row['values'][$key]['key'], $report->options['Charts'][$num]['columns']))!==false) {
						$cols[$temp] = $key;
					}
					//if all columns are included, add after any specifically defined ones
					elseif($report->options['Charts'][$num]['all']) {
						$cols[$unsorted] = $key;
						$unsorted ++;
					}
				}
				
				ksort($cols);
			}
			
			foreach($cols as $key) {
				if(isset($row['values'][$key]['chartval']) && is_array($row['values'][$key]['chartval'])) {
					foreach($row['values'][$key]['chartval'] as $ckey=>$cval) {
						$temp = array();
						$temp['raw_value'] = trim($cval,'%$ ');
						$temp['value'] = $cval;
						$temp['key'] = $ckey;
						$temp['first'] = !$vals;
						$vals[] = $temp;
					}
				}
				else {
					$row['values'][$key]['raw_value'] = trim($row['values'][$key]['raw_value'],'%$ ');
					$vals[] = $row['values'][$key];
				}
			}
			
			$chart_rows[] = $vals;
		}
		
		//determine column types
		$types = array();
		foreach($chart_rows as $i=>$row) {
			foreach($row as $k=>$v) {
				$type = self::determineDataType($v['raw_value']);
				if(!$type) continue;
				elseif(!isset($types[$k])) $types[$k] = $type;
				elseif($type === 'string') $types[$k] = 'string';
				elseif($types[$k] === 'date' && $type === 'number') $types[$k] = 'number';
			}
		}
		
		$report->options['Charts'][$num]['datatypes'] = $types;
		
		//build chart rows
		$report->options['Charts'][$num]['Rows'] = array();
		
		foreach($chart_rows as $i=>&$row) {
			$vals = array();
			foreach($row as $key=>$values) {	
				$val = array(
					'key'=>$values['key'],
					'is_date'=>false,
					'is_number'=>false,
					'is_string'=>false,
					'is_null'=>false,
					'first'=>!$vals,
					'value'=>$values['raw_value']
				);
				
				if(is_null($val['value'])) {
					$val['is_null'] = true;
				}
				elseif($types[$key] === 'date') {
					$val['value'] = date('m/d/Y H:i:s',strtotime($val['value']));
					$val['is_date'] = true;
				}
				elseif($types[$key] === 'number') {
					$val['value'] = round(floatval(preg_replace('/[^0-9\.]*/','',$val['value'])),6);
					$val['is_number'] = true;
				}
				elseif($types[$key] === 'string') {
					$val['is_string'] = true;
				}
				
				$vals[] = $val;
			}
			
			$report->options['Charts'][$num]['Rows'][] = array(
				'values'=>$vals,
				'first'=>!$report->options['Charts'][$num]['Rows']
			);
		}
	}
	
	protected static function generateHistogramRows($rows, $column, $num_buckets) {
		$column_key = null;
		
		//if a name is given as the column, determine the column index
		if(!is_numeric($column)) {
			foreach($rows[0]['values'] as $k=>$v) {
				if($v['key'] == $column) {
					$column = $k;
					$column_key = $v['key'];
					break;
				}
			}
		}
		//if an index is given, convert to 0-based
		else {
			$column --;
			$column_key = $rows[0]['values'][$column]['key'];
		}
		
		//get a list of values for the histogram
		$vals = array();
		foreach($rows as &$row) {
			$vals[] = floatval(preg_replace('/[^0-9.]*/','',$row['values'][$column]['raw_value']));
		}
		sort($vals);
		
		//determine buckets
		$count = count($vals);
		$buckets = array();
		$min = $vals[0];
		$max = $vals[$count-1];
		$step = ($max-$min)/$num_buckets;
		$old_limit = $min;
		
		for($i=1;$i<$num_buckets+1;$i++) {
			$limit = $old_limit + $step;
			
			$buckets[round($old_limit,2)." - ".round($limit,2)] = count(array_filter($vals,function($val) use($old_limit,$limit) {
				return $val >= $old_limit && $val < $limit;
			}));
			$old_limit = $limit;
		}
		
		//build chart rows
		$chart_rows = array();
		foreach($buckets as $name=>$count) {
			$chart_rows[] = array(
				'values'=>array(
					array(
						'raw_value'=>$name,
						'value'=>$name,
						'key'=>$name,
						'first'=>true
					),
					array(
						'raw_value'=>$count,
						'value'=>$count,
						'key'=>'value',
						'first'=>false
					)
				),
				'first'=>!$chart_rows
			);
		}
		return $chart_rows;
	}
	
	protected static function determineDataType($value) {
		if(is_null($value)) return null;
		elseif(preg_match('/^([$%(\-+\s])*([0-9,]+(\.[0-9]+)?|\.[0-9]+)([$%(\-+\s])*$/',$value)) return 'number';
		elseif(strtotime($value)) return 'date';
		else return 'string';
	}

	public static function beforeRender(&$report) {		
		foreach($report->options['Charts'] as $num=>&$params) {
			if(isset($params['xhistogram']) && $params['xhistogram']) {
				$rows = self::generateHistogramRows($report->options['Rows'],$params['columns'][0],$params['buckets']);
				$params['columns'] = array(1,2);
			}
			else {
				$rows = $report->options['Rows'];
			}
			
			self::getRowInfo($rows, $params, $num, $report);
		}
	}
}
