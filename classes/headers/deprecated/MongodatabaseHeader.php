<?php
class MongodatabaseHeader extends OptionsHeader {
	public static function parseShortcut($value) {
		return array(
			'Mongodatabase'=>$value
		);
	}
}
