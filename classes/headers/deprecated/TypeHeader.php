<?php
class TypeHeader extends InfoHeader {
	public static function parseShortcut($value) {
		return array(
			'type'=>$value
		);
	}
}
