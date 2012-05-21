<?php
class barFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report = null) {
		if(isset($options['width'])) $max = $options['width'];
		else $max = 200;
		
		$width = round($max*($value['raw_value']/max($report->options['Values'][$value['key']])));
		
		$value['value'] = "<div style='width: ".$width."px;' class='bar'></div>";
		$value['value'] .= "<span style='color:#999; font-size:.8em; vertical-align:middle;'>".$value['raw_value']."</span>";
		$value['raw'] = true;
		
		return $value;
	}
}
