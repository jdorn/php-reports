<?php
class RollupHeader extends HeaderBase {
	static $validation = array(
		'columns'=>array(
			'required'=>true,
			'type'=>'object',
			'default'=>array()
		),
		'dataset'=>array(
			'required'=>false,
			'default'=>0
		)
	);
	
	public static function init($params, &$report) {
		//make sure at least 1 column is defined
		if(empty($params['columns'])) throw new Exception("Rollup header needs at least 1 column defined");
		
		if(!isset($report->options['Rollup'])) $report->options['Rollup'] = array();
		
		// If more than one dataset is defined, add the rollup header multiple times
		if(is_array($params['dataset'])) {
			$new_params = $params;
			foreach($params['dataset'] as $dataset) {
				$new_params['dataset'] = $dataset;
				$report->options['Rollup'][] = $new_params;
			}
		}
		// Otherwise, just add one rollup header
		else {
			$report->options['Rollup'][] = $params;
		}
	}
	
	public static function beforeRender(&$report) {			
		//cache for Twig parameters for each dataset/column
		$twig_params = array();
		
		// Now that we know how many datasets we have, expand out Rollup headers with dataset->true
		$new_rollups = array();
		foreach($report->options['Rollup'] as $i=>$rollup) {
			if($rollup['dataset']===true && isset($report->options['DataSets'])) {
				$copy = $rollup;
				foreach($report->options['DataSets'] as $i=>$dataset) {
					$copy['dataset'] = $i;
					$new_rollups[] = $copy;
				}
			}
			else {
				$new_rollups[] = $rollup;
			}
		}
		$report->options['Rollup'] = $new_rollups;
		
		// First get all the values
		foreach($report->options['Rollup'] as $rollup) {			
			// If we already got twig parameters for this dataset, skip it
			if(isset($twig_params[$rollup['dataset']])) continue;
			$twig_params[$rollup['dataset']] = array();
			if(isset($report->options['DataSets'])) {
				if(isset($report->options['DataSets'][$rollup['dataset']])) {
					foreach($report->options['DataSets'][$rollup['dataset']]['rows'] as $row) {
						foreach($row['values'] as $value) {
							if(!isset($twig_params[$rollup['dataset']][$value->key])) $twig_params[$rollup['dataset']][$value->key] = array('values'=>array());
							$twig_params[$rollup['dataset']][$value->key]['values'][] = $value->getValue();
						}
					}
				}
			}
		}

		// Then, calculate other statistical properties
		foreach($twig_params as $dataset=>&$tp) {
			foreach($tp as $column=>&$params) {
				//get non-null values and sort them
				$real_values = array_filter($params['values'],function($a) {if($a === null || $a==='') return false; return true; });
				sort($real_values);
				
				$params['sum'] = array_sum($real_values);
				$params['count'] = count($real_values);
				if($params['count']) {
					$params['mean'] = $params['average'] = $params['sum'] / $params['count'];

					// Median for odd number of rows
					if($params['count']%2) {
						$params['median'] = $real_values[floor($params['count']/2)];
					}
					// Median for even number of rows
					else {
						// If the 2 middle entries are numeric, average them
						if(is_numeric($real_values[$params['count']/2-1]) && is_numeric($real_values[$params['count']/2])) {
							$params['median'] = ($real_values[$params['count']/2-1] + $real_values[$params['count']/2])/2;
						}
						// If they are not numeric, pick one of the middle entries
						else {
							$params['median'] = $real_values[$params['count']/2-1];
						}
					}

					$params['min'] = $real_values[0];
					$params['max'] = $real_values[$params['count']-1];
				}
				else {
					$params['mean'] = $params['average'] = $params['median'] = $params['min'] = $params['max'] = 0;
				}
				
				$devs = array();
				if (empty($real_values)) {
					$params['stdev'] = 0;
				} else 	if (function_exists('stats_standard_deviation')) {
					$params['stdev'] = stats_standard_deviation($real_values);
				} else {
					foreach($real_values as $v) $devs[] = pow($v - $params['mean'], 2);
					$params['stdev'] = sqrt(array_sum($devs) / (count($devs)));
				}
			}
		}
		
		//render each rollup row
		foreach($report->options['Rollup'] as $rollup) {
			if(!isset($report->options['DataSets'][$rollup['dataset']]['footer'])) $report->options['DataSets'][$rollup['dataset']]['footer'] = array();
			$columns = $rollup['columns'];
			$row = array(
				'values'=>array(),
				'rollup'=>true
			);
			
			foreach($twig_params[$rollup['dataset']] as $column=>$p) {
				if(isset($columns[$column])) {
					$p = array_merge($p,array('row'=>$twig_params[$rollup['dataset']]));

					$row['values'][] = new ReportValue(-1,$column,PhpReports::renderString($columns[$column],$p));
				}
				else {
					$row['values'][] = new ReportValue(-1,$column,null);
				}
			}
			$report->options['DataSets'][$rollup['dataset']]['footer'][] = $row;
		}
	}
}
