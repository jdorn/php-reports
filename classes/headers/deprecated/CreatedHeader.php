<?php
class CreatedHeader extends InfoHeader {
	public static function parseShortcut($value) {
		return array(
			'created'=>$value
		);
	}
}
