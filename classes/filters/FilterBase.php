<?php
abstract class FilterBase {	
	/**
	 * Filter a datapoint in the report
	 * @param String $value - The current value
	 * @param array $options - An array of filter options
	 * @param Report $report - The report object
	 * @return String The filtered value or false if the column should not be shown
	 */
	abstract public static function filter($value, $options=array(), $report=null);
}
