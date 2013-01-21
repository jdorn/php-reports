<?php
class RollupHeader extends HeaderBase {
	static $validation = array(
		'columns'=>array(
			'required'=>true,
			'type'=>'object',
			'default'=>array()
		)
	);
	
	public static function init($params, &$report) {
		//make sure at least 1 column is defined
		if(empty($params['columns'])) throw new Exception("Rollup header needs at least 1 column defined");
		
		//store all the rollup rows in the report
		if(!isset($report->options['Rollup'])) $report->options['Rollup'] = array();
		$report->options['Rollup'][] = $params;
	}
	
	public static function beforeRender(&$report) {
		//cache for Twig parameters for each column
		$twig_params = array();
		foreach($report->options['Rows'] as $row) {
			foreach($row['values'] as $value) {
				if(!isset($twig_params[$value->key])) $twig_params[$value->key] = array('values'=>array());
				$twig_params[$value->key]['values'][] = $value->getValue();
			}
		}
		foreach($twig_params as $column=>&$params) {
			//get non-null values and sort them
			$real_values = array_filter($params['values'],function($a) {if($a === null || $a==='') return false; return true; });
			sort($real_values);
			
			$params['sum'] = array_sum($real_values);
			$params['count'] = count($real_values);
			$params['mean'] = $params['average'] = $params['sum'] / $params['count'];			
			$params['median'] = ($params['count']%2)? ($real_values[$params['count']/2] + $real_values[$params['count']/2+1])/2 : $real_values[ceil($params['count']/2)];
			$params['min'] = $real_values[0];
			$params['max'] = $real_values[$params['count']-1];
			
			$devs = array();
			foreach($real_values as $v) $devs[] = pow($v - $params['mean'], 2);
			$params['stdev'] = sqrt(array_sum($devs) / (count($devs) - 1));
		}
			
		if(!isset($report->options['FooterRows'])) $report->options['FooterRows'] = array();
		
		//render each rollup row
		foreach($report->options['Rollup'] as $rollup) {
			$columns = $rollup['columns'];
			$row = array(
				'values'=>array(),
				'rollup'=>true
			);
			
			foreach($twig_params as $column=>$p) {
				if(isset($columns[$column])) {
					$row['values'][] = new ReportValue(-1,$column,PhpReports::renderString($columns[$column],$p));
				}
				else {
					$row['values'][] = new ReportValue(-1,$column,null);
				}
			}
			$report->options['FooterRows'][] = $row;
		}
	}
}
