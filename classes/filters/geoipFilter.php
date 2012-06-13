<?php
class geoipFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		$record = geoip_record_by_name($value['raw_value']);
		
		if($record) {
			$value['value'] = $record['city'];
			if($record['country_code'] !== 'US') {
				$value['value'] .= ' '.$record['country_name'];
			}
			else {
				$value['value'] .= ', '.$record['region'];
			}
			$value['chartval'] = array('Latitude'=>$record['latitude'],'Longitude'=>$record['longitude'],'Location'=>$value['value']);
		}
		else {
			$value['chartval'] = array('Latitude'=>0, 'Longitude'=>0, 'Location'=>'Unknown');
		}
		
		
		$value['value'] = trim($value['value']);
		
		return $value;
	}
}
