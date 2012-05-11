<?php
interface FilterInterface {
	/**
	 * Return false if the value contains html that shouldn't be escaped
	 */
	public static function canEscape();
	
	/**
	 * Filter a datapoint in the report
	 * @param String $key - The column the data point is in
	 * @param String $value - The current value
	 * @param array $options - An array of filter options
	 * @return String The filtered value
	 */
	public static function filter($key, $value, $options=array());
}
