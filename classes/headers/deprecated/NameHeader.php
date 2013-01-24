<?php
class NameHeader extends InfoHeader {
	public static function parseShortcut($value) {		
		return array(
			'name'=>$value
		);
	}
}
