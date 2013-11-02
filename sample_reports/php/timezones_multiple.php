<?php
// Get All Timezones By Region
// INCLUDE: {report: "functions.php"}
// VARIABLE: {
//      name: "regions",
//      multiple: true,
//      display: "Region Code",
//      type: "select",
//      options: ["cdt","acst","pdt"],
//      default: "acst"
// }

$timezone_abbreviations = DateTimeZone::listAbbreviations();

// Build report dataset
$datasets = array();
$limit = 5;
$i=0;
foreach($regions as $region) {
    $timezones = $timezone_abbreviations[$region];
    
    $dataset = array(
        'rows'=>array(),
        'title'=>$region
    );
    foreach($timezones as $timezone) {
        $dataset['rows'][] = array(
            'Timezone'=>$timezone['timezone_id'],
            'Offset'=>$timezone['offset']/3600
        );
    }
    
    $stats = array_stats($dataset['rows'], 'Offset');
    $dataset['rows'][] = array(
        'Timezone'=>'AVERAGE',
        'Offset'=>$stats['mean']
    );
    
    $datasets[] = $dataset;
}

// Output the rows
echo json_encode($datasets);
