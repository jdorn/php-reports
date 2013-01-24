<?php
class barFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		if(isset($options['width'])) $max = $options['width'];
		else $max = 200;
		
		$width = round($max*($value->getValue()/max($report->options['Values'][$value->key])));
		
		$value->setValue("<div style='width: ".$width."px;' class='bar'></div>"."<span style='color:#999; font-size:.8em; vertical-align:middle;'>".$value->getValue(true)."</span>",true);
		
		return $value;
	}
}
