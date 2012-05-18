<?php
abstract class ReportTypeBase {
	public static function init(&$report) {
		
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	abstract public static function run(&$report);
}
