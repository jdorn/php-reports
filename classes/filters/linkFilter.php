<?php
class linkFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		if(!$value->getValue()) return $value;
		
		$url = isset($options['url'])? $options['url'] : $value->getValue();
		$attr = (isset($options['blank']) && $options['blank'])? ' target="_blank"' : '';
		$display = isset($options['display'])? $options['display'] : $value->getValue();

		$html = '<a href="'.$url.'"'.$attr.'>'.$display.'</a>';
		
		$value->setValue($html, true);
		
		return $value;
	}
}
