<?php
class linkFilter extends FilterBase {	
	public static function filter($value, $options = array()) {
		$value['value'] = '<a href="'.$value['value'].'"'.($options['blank']? ' target="_blank"':'').'>'.(isset($options['display'])? $options['display'] : $value['value']).'</a>';
		$value['raw'] = true;
		
		return $value;
	}
}
