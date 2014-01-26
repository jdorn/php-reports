<?php
class ChartHeader extends HeaderBase {
	static $validation = array(
		'columns'=>array(
			'type'=>'array',
			'default'=>array()
		),
		'dataset'=>array(
			'default'=>0
		),
		'type'=>array(
			'type'=>'enum',
			'values'=>array('LineChart','GeoChart','AnnotatedTimeLine','BarChart','ColumnChart','Timeline'),
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
		'colors'=>array(
			'type'=>'array',
			'default'=>array()
		),
		'markers'=>array(
			'type'=>'boolean',
			'default'=>false
		),
        'omit-columns'=>array(
            'type'=>'array',
            'default'=>array()
        ),
		'options'=>array(
			'type'=>'object',
			'default'=>array()
		)
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
				
				//some parameters can have multiple values separated by ":"
				if(in_array($key,array('x','y','colors'),true)) {
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
	
	protected static function getRowInfo(&$rows, $params, $num, &$report) {
		$cols = array();
		
		//expand columns
		$chart_rows = array();
		foreach($rows as $k=>$row) {			
			$vals = array();
			
			if($k===0) {
				$i=1;
				$unsorted = 1000;
				foreach($row['values'] as $key=>$value) {
					if (($temp = array_search($row['values'][$key]->i, $report->options['Charts'][$num]['columns']))!==false) {
						$cols[$temp] = $key;
					} elseif (($temp = array_search($row['values'][$key]->key, $report->options['Charts'][$num]['columns']))!==false) {
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
				if(isset($row['values'][$key]->chart_value) && is_array($row['values'][$key]->chart_value)) {
					foreach($row['values'][$key]->chart_value as $ckey=>$cval) {
						$temp = new ReportValue($row['values'][$key]->i, $ckey, trim($cval,'%$ '));
						$temp->setValue($cval);
						$vals[] = $temp;
					}
				} else {
					$temp = new ReportValue($row['values'][$key]->i, $row['values'][$key]->key, $row['values'][$key]->original_value);
					$temp->setValue(trim($row['values'][$key]->getValue(),'%$ '));
					$vals[] = $temp;
				}
			}
			
			$chart_rows[] = $vals;
		}
		
		//determine column types
		$types = array();
		foreach($chart_rows as $i=>$row) {
			foreach($row as $k=>$v) {
				$type = self::determineDataType($v->original_value);
				//if the value is null, it doesn't influence the column type
				if(!$type) {
					$chart_rows[$i][$k]->setValue(null);
					continue;
				}
				//if we don't know the column type yet, set it to this row's value
				elseif(!isset($types[$k])) $types[$k] = $type;
				//if any row has a string value for the column, the whole column is a string type
				elseif($type === 'string') $types[$k] = 'string';
				//if the column is currently a date and this row is a time/datetime, set the column to datetime type
				elseif($types[$k] === 'date' && in_array($type,array('timeofday','datetime'))) $types[$k] = 'datetime';
				//if the column is currently a time and this row is a date/datetime, set the column to datetime type
				elseif($types[$k] === 'timeofday' && in_array($type,array('date','datetime'))) $types[$k] = 'datetime';
				//if the column is currently a date and this row is a number set the column type to number
				elseif($types[$k] === 'date' && $type === 'number') $types[$k] = 'number';
			}
		}
		
		$report->options['Charts'][$num]['datatypes'] = $types;
		
		//build chart rows
		$report->options['Charts'][$num]['Rows'] = array();
		
		foreach($chart_rows as $i=>&$row) {
			$vals = array();
			foreach($row as $key=>$val) {			
				if(is_null($val->getValue())) {
					$val->datatype = 'null';
				}
				elseif($types[$key] === 'datetime') {
					$val->setValue(date('m/d/Y H:i:s',strtotime($val->getValue())));
					$val->datatype = 'datetime';
				}
				elseif($types[$key] === 'timeofday') {
					$val->setValue(date('H:i:s',strtotime($val->getValue())));
					$val->datatype = 'timeofday';
				}
				elseif($types[$key] === 'date') {
					$val->setValue(date('m/d/Y',strtotime($val->getValue())));
					$val->datatype = 'date';
				}
				elseif($types[$key] === 'number') {
					$val->setValue(round(floatval(preg_replace('/[^-0-9\.]*/','',$val->getValue())),6));
					$val->datatype = 'number';
				}
				else {
					$val->datatype = 'string';
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
				if($v->key == $column) {
					$column = $k;
					$column_key = $v->key;
					break;
				}
			}
		}
		//if an index is given, convert to 0-based
		else {
			$column --;
			$column_key = $rows[0]['values'][$column]->key;
		}
		
		//get a list of values for the histogram
		$vals = array();
		foreach($rows as &$row) {
			$vals[] = floatval(preg_replace('/[^0-9.]*/','',$row['values'][$column]->getValue()));
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
					new ReportValue(1,$name,$name),
					new ReportValue(2,'value',$count)
				),
				'first'=>!$chart_rows
			);
		}
		return $chart_rows;
	}
	
	protected static function determineDataType($value) {
		if(is_null($value)) return null;
		elseif($value === '') return null;
		elseif(preg_match('/^([$%(\-+\s])*([0-9,]+(\.[0-9]+)?|\.[0-9]+)([$%(\-+\s])*$/',$value)) return 'number';
		elseif($temp = strtotime($value)) {
			if(preg_match('/^[0-2][0-9]:/',$value)) return 'timeofday';
			elseif(date('H:i:s',$temp) === '00:00:00') return 'date';
			else return 'datetime';
		}
		else return 'string';
	}

	public static function beforeRender(&$report) {	
		// Expand out multiple datasets into their own charts
		$new_charts = array();
		foreach($report->options['Charts'] as $num=>$params) {
			$copy = $params;
			
			// If chart is for multiple datasets
			if(is_array($params['dataset'])) {
				foreach($params['dataset'] as $dataset) {
					$copy['dataset'] = $dataset;
					$copy['num'] = count($new_charts)+1;
					$new_charts[] = $copy;
				}
			}
			// If chart is for all datasets
			elseif($params['dataset']===true) {
				foreach($report->options['DataSets'] as $j=>$dataset) {
					$copy['dataset'] = $j;
					$copy['num'] = count($new_charts)+1;
					$new_charts[] = $copy;
				}
			}
			// If chart is for one dataset
			else {
				$copy['num'] = count($new_charts)+1;
				$new_charts[] = $copy;
			}
		}
		
		$report->options['Charts'] = $new_charts;
		
		foreach($report->options['Charts'] as $num=>&$params) {
			self::_processChart($num,$params,$params['dataset'],$report);
		}
	}
	protected static function _processChart($num, &$params, $dataset, &$report) {
		if(isset($params['xhistogram']) && $params['xhistogram']) {
			$rows = self::generateHistogramRows($report->options['DataSets'][$dataset]['rows'],$params['columns'][0],$params['buckets']);
			$params['columns'] = array(1,2);
		}
		else {
			$rows = $report->options['DataSets'][$dataset]['rows'];

			if(!$params['columns']) $params['columns'] = range(1,count($rows[0]['values']));
		}
		
		self::getRowInfo($rows, $params, $num, $report);
	}
}
