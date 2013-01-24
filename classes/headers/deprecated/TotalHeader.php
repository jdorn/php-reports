<?php
class TotalHeader extends TotalsHeader {
	public static function init($params, &$report) {
		trigger_error("TOTAL header is deprecated.  Use the ROLLUP header instead.",E_USER_DEPRECATED);
	}
}
