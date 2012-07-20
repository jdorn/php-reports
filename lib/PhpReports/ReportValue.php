<?php
class ReportValue {
	public $key;
	public $i;
	
	public $original_value;
	public $filtered_value;
	public $html_value;
	public $chart_value;
	
	public $is_html;
	public $type;
	
	public $class;
	
	public function __construct($i, $key, $value) {
		$this->i = $i;
		$this->key = $key;
		$this->original_value = $value;
		$this->filtered_value = strip_tags($value);
		$this->html_value = $value;
		$this->chart_value = $value;
		
		$this->is_html = false;
		$this->class = '';
	}
	
	public function addClass($class) {
		$this->class = trim($this->class . ' ' .$class);
	}
	
	public function setValue($value, $html = false) {
		if(is_string($value)) $value = trim($value);
		
		if($html) {
			$this->is_html = true;
			$this->html_value = $value;
		}
		else {
			$this->is_html = false;
			$this->filtered_value = is_string($value)? htmlentities($value) : $value;
			$this->html_value = $value;
		}
	}
	
	public function getValue($html = false) {		
		if($html) return is_string($this->html_value)? utf8_encode($this->html_value) : $this->html_value;
		else return is_string($this->filtered_value)? utf8_encode($this->filtered_value) : $this->filtered_value;
	}
	
	public function getKeyCollapsed() {
		return trim(preg_replace(array('/\s+/','/[^a-zA-Z0-9_]*/'),array('_',''),$this->key),'_');
	}
}
