<?php
// Helper PHP Functions
// This report is never run on it's own.
// It is only ever used when it's included from within other reports
// OPTIONS: { ignore: true }

/**
 * Gets the mean and median from an array
 * @param $array array An array of associative arrays.
 * @param $key string The key of numeric data for each element of $array
 * @return array An associative array containing the keys: "sum", "mean", and "median"
 */
function array_stats($array, $key) {
    $numbers = array();
    foreach($array as $el) {
        if(isset($el[$key]) && is_numeric($el[$key])) {
            $numbers[] = $el[$key];
        }
    }
    
    if(!$numbers) return array(
        'sum'=>0,
        'mean'=>0,
        'median'=>0
    );
    
    // This is horribly inefficient, but that's ok for demo purposes
    return array(
        'sum'=>array_sum($numbers),
        'median'=>(count($numbers)%2)? 
            ($numbers[count($numbers)/2] + $numbers[count($numbers)/2+1])/2 : 
            $numbers[ceil(count($numbers)/2)],
        'mean'=>(array_sum($numbers) / count($numbers))
    );
}
