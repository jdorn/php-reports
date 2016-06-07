<?php
class DynamicHeader extends HeaderBase {

	static $validation = array(
		'file'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {

      if($params['file'][0] === '/') {

			$file_path = substr($params['file'],1);

		}
		else {

			$file_path = dirname($report->report).'/'.$params['file'];

		}

		if(file_exists(PhpReports::$config['reportDir'].'/'.$file_path)) {

         include(PhpReports::$config['reportDir'].'/'.$file_path);

         /**
         * This allows you to specify headers in php,
         * which allows you to build the properties dynamically.
         */
         if (isset($headers) and count($headers)) {

            foreach ($headers as $header) {

               $report->parseHeader($header['name'], $header['params']);

            }
         }

         /**
          * Similarly this allows you to specify macros
          * in php so you can disallow various headers,
          * substituting them with macros.
          */
         if (isset($macros) and count($macros)) {

            foreach ($macros as $key => $value) {
               $report->addMacro($key, $value);
            }

         }

		}
	}
	
	public static function parseShortcut($value) {
		return array(
			'file'=>$value
		);
	}
}

?>
