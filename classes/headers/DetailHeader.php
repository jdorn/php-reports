<?php
class DetailHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		if(!isset($report->options['Detail'])) $report->options['Detail'] = array();
		
		$parts = explode(',',$value);
		
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
		
		$temp = explode('/',$report->report);
		array_pop($temp);
		$report_name = implode('/',$temp).'/'.$report_name;
		if(!file_exists(PhpReports::$config['reportDir'].'/'.$report_name)) {
			$possible_reports = glob(PhpReports::$config['reportDir'].'/'.$report_name.'*');
			
			if($possible_reports) {
				$report_name = substr($possible_reports[0],strlen(PhpReports::$config['reportDir'].'/'));
			}
			else {
				throw new Exception("Unknown report in DETAIL header '$report_name'");
			}
		}
		
		$report->options['Detail'][$col] = array(
			'report'=>$report_name,
			'macros'=>$macros
		);
	}
	
	public static function beforeRender(&$report) {
		$details = $report->options['Detail'];
		
		//map columns to keys
		$cols = array();
		foreach($report->options['Rows'][0]['values'] as $key=>$value) {
			$cols[$value['key']] = $key;
		}
		
		foreach($report->options['Rows'] as &$row) {
			foreach($details as $key=>&$detail) {
				if(isset($cols[$key])) $i = $cols[$key];
				else $i = $key-1;
								
				$url = PhpReports::$request->base.'/report/html/?report='.$detail['report'];
				
				$macros = array();
				foreach($detail['macros'] as $k=>$v) {
					//if the macro needs to be replaced with the value of another column
					if(isset($v['column'])) {
						if(isset($cols[$v['column']])) {
							$v = $row['values'][$cols[$v['column']]]['raw_value'];
						}
						else $v = $row['values'][$v['column']-1]['raw_value'];
					}
					//if the macro is just a constant
					elseif(isset($v['constant'])) {
						$v = $v['constant'];
					}
					
					$macros[$k] = $v;
				}
				
				$macros = array_merge($report->macros,$macros);
				unset($macros['host']);
				
				foreach($macros as $k=>$v) {									
					if(is_array($v)) {
						foreach($v as $v2) {
							$url .= '&macros['.$k.'][]='.$v2;
						}
					}
					else {
						$url.='&macros['.$k.']='.$v;
					}
				}
				
				$row['values'][$i]['value'] = '<a href="'.htmlentities($url).'">'.$row['values'][$i]['value'].'</a>';
				$row['values'][$i]['raw'] = true;
			}
		}
	}
}
