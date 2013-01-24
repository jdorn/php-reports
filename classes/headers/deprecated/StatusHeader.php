<?php
class StatusHeader extends InfoHeader {	
	public static function parseShortcut($value) {
		return array(
			'status'=>$value
		);
	}
}
