<?php
//This is a test report
//VARIABLE: {
//	name: 'start',
//	display: 'Start Date',
//	default: '+1 week',
//	type: 'date'
//}

$data = array(
	array(
		'col1'=>'value 1',
		'col2'=>$start
	),
	array(
		'col1'=>'value 3',
		'col2'=>'value 4'
	)
);

echo json_encode($data);
