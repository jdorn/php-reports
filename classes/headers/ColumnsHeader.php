<?php
class ColumnsHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		if($temp = json_decode($value,true)) {
			$value = $temp;
			
			foreach($value as $column=>$options) {
				$type = $options['type'];
				$report->addFilter($column,$type,$options);
			}
		}
		else {
			$parts = explode(',',$value);
			$value = array();
			$i = 1;
			foreach($parts as $part) {
				$type = null;
				$options = null;
				
				$part = trim($part);
				//special cases
				//'rpadN' or 'lpadN' where N is number of spaces to pad
				if(substr($part,1,3)==='pad') {
					$type = 'padding';
					
					$options = array(
						'type'=>$part[0],
						'spaces'=>intval(substr($part,4))
					);
				}
				//link or link(display) or link_blank or link_blank(display)
				elseif(substr($part,0,4)==='link') {
					//link(display) or link_blank(display)
					if(strpos($part,'(') !== false) {
						list($type,$display) = explode('(',substr($part,0,-1),2);
					}
					else {
						$type = $part;
						$display = 'link';
					}
					
					$blank = ($type == 'link_blank');
					$type = 'link';
					
					$options = array(
						'display'=>$display,
						'blank'=>$blank
					);
				}
				//synonyms for 'html'
				elseif(in_array($part,array('html','raw'))) {
					$type = 'html';
				}
				//url synonym for link
				elseif($part === 'url') {
					$type = 'link';
					$options = array(
						'blank'=>false,
						'display'=>$part
					);
				}
				elseif($part === 'bar') {
					$type = 'bar';
					$options = array();
				}
				//normal case
				else {
					$type = 'class';
					$options = array(
						'class'=>$part
					);
				}
				
				$report->addFilter($i, $type, $options);
				$i++;
			}
		}
	}
}
