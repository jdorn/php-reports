<?php
class imgsizeFilter extends FilterBase {	
	static $default_format = '{{ geometry.width }}x{{ geometry.height }} {{ compression }}, {{ fileSize }}';
	
	public static function filter($value, $options = array(), &$report, &$row) {
		$handle = fopen($value->getValue(), 'rb');
		$img = new Imagick();
		$img->readImageFile($handle);
		$data = $img->identifyImage();
		
		if(!isset($options['format'])) $options['format'] = self::$default_format;
		
		$value->setValue(PhpReports::renderString($options['format'], $data));
		
		return $value;
	}
}
