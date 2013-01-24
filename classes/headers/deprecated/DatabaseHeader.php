<?php
class DatabaseHeader extends OptionsHeader {
	public static function parseShortcut($value) {
		return array(
			'Database'=>trim($value)
		);
	}
}
