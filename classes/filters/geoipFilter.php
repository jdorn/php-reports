<?php
class geoipFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		$record = geoip_record_by_name($value->getValue());
		
		if($record) {
			$display = '';
			
			$display = $record['city'];
			if($record['country_code'] !== 'US') {
				$display .= ' '.$record['country_name'];
			}
			else {
				$display .= ', '.$record['region'];
			}
			
			$value->setValue($display);
			
			$value->chart_value = array('Latitude'=>$record['latitude'],'Longitude'=>$record['longitude'],'Location'=>$display);
		}
		else {
			$value->chart_value = array('Latitude'=>0, 'Longitude'=>0, 'Location'=>'Unknown');
		}
		
		return $value;
	}
}
