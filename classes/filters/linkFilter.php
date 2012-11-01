<?php
class linkFilter extends FilterBase {	
	public static function filter($value, $options = array(), $report=null) {
		if(!$value->getValue()) return $value;

		$html = '<a href="'.$value->getValue().'"'.($options['blank']? ' target="_blank"':'').'>'.(isset($options['display'])? $options['display'] : $value->getValue(true)).'</a>';
		$value->setValue($html, true);
		
		return $value;
	}
}
