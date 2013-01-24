<?php
class DescriptionHeader extends InfoHeader {
	public static function parseShortcut($value) {
		return array(
			'description'=>$value
		);
	}
}
