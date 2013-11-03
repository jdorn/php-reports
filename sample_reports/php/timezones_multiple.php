<?php
// Get All Timezones By Region
// VARIABLE: {
//      name: "regions",
//      multiple: true,
//      display: "Region Code",
//      type: "select",
//      options: ["cdt","acst","pdt"],
//      default: "acst"
// }
// ROLLUP: {columns: {Timezone: "AVERAGE", Offset: "{{mean}}"}, dataset: [0,1]}

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
    
    $datasets[] = $dataset;
}

// Output the rows
echo json_encode($datasets);
