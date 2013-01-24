<?php
class ColumnHeader extends ColumnsHeader {
	public static function init($params, &$report) {
		trigger_error("COLUMN header is deprecated.  Use the COLUMNS header instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
}
