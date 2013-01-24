<?php
//This is for backwards compatibility
//The Chart header used to be called Plot
class PlotHeader extends ChartHeader {
	public static function init($params, &$report) {
		trigger_error("PLOT header is deprecated.  Use the CHART header instead.",E_USER_DEPRECATED);
		
		return parent::init($params, $report);
	}
}
