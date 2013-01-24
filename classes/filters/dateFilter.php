<?php
class dateFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		if(!isset($options['format'])) $options['format'] = (isset(PhpReports::$config['default_date_format'])? PhpReports::$config['default_date_format'] : 'Y-m-d H:i:s');
		if(!isset($options['database'])) $options['database'] = $report->options['Database'];
		
		$time = strtotime($value->getValue());
		
		//if the time couldn't be parsed, just return the original value
		if(!$time) {
			return $value;
		}
		
		//if a timezone correction is needed for the database being selected from
		$environment = $report->getEnvironment();
		if(isset($environment[$options['database']]['time_offset'])) {
			$time_offset = -1*$environment[$options['database']]['time_offset'];
			
			$time = strtotime((($time_offset > 0)? '+' : '-').abs($time_offset).' hours',$time);
		}
		
		$value->setValue(date($options['format'],$time));
		
		return $value;
	}
}
