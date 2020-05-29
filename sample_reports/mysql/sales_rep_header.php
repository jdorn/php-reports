<?php
// Dynamic Sales Rep Header
// OPTIONS: {"ignore": true}

if (isset($sales_rep_id) && $sales_rep_id > 0) {

   $macros['sales_rep_id'] = $sales_rep_id;

} else {

   $header['name'] = 'VARIABLE';
   $header['params'] = array(
      'name' => 'sales_rep_id',
      'display' => 'Sales Rep',
      'type' => 'select',
      'empty' => true,
      'database_options' => array(
            'table'   => 'sales_reps',
            'column'  => 'id',
            'display' => 'name',
            'where'   => 'active = "y"',
            'all'     => true
      )
   );

   $headers[] = $header;
}



?>
