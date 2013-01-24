<?php
class NoteHeader extends InfoHeader {
	public static function parseShortcut($value) {
		return array(
			'note'=>$value
		);
	}
}
