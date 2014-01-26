<?php
// Get Timezones Contained In a Region
// INCLUDE: {report: "functions.php"}
// VARIABLE: {
//      name: "region",
//      display: "Region Code",
//      type: "select",
//      options: ["cdt","acst","pdt"],
//      default: "acst"
// }
// CHART: {type: "LineChart"}

$timezone_abbreviations = DateTimeZone::listAbbreviations();

// If an invalid timezone abbreviation is passed in, show an error to the user
if(!isset($timezone_abbreviations[$region])) throw new Exception("Invalid region - ".$region);

// Build report rows
$rows = array();
foreach($timezone_abbreviations[$region] as $timezone) {
    $rows[] = array(
        'Timezone'=>$timezone['timezone_id'],
        'Offset'=>$timezone['offset']/3600
    );
}

// Add an AVERAGE Row at the bottom
// The array_stats function is defined in the included report functions.php
// This is just a demo.  Normally, you would just use the ROLLUP header for something like this
$stats = array_stats($rows, 'Offset');
$rows[] = array(
    'Timezone'=>'AVERAGE',
    'Offset'=>$stats['mean']
);

// Output the rows
echo json_encode($rows);
