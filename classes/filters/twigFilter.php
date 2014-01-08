<?php
class twigFilter extends FilterBase {	
	public static function filter($value, $options = array(), &$report, &$row) {
		// If this is html
		$html = isset($options['html'])? $options['html'] : false;
		
		$template = isset($options['template'])? $options['template'] : $value->getValue();
		
		$result = PhpReports::renderString($template,array(
			"value"=>$value->getValue(),
			"row"=>$row
		));
		
		$value->setValue($result, $html);
		
		return $value;
	}
}
