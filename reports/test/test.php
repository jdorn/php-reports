<?php
//This is a test report

$data = array(
	array(
		'col1'=>'value 1',
		'col2'=>'value 2'
	),
	array(
		'col1'=>'value 3',
		'col2'=>'value 4'
	)
);

echo json_encode($data);
