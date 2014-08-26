<?php
return array(
    
        //Confugure whether to use Login System or not for PHPReports
        //Assign 1 to enable, assign 0 for disable. Default it is '0' (Disabled)
        'loginEnable' => 1,
        
	//the root directory of all your reports
	//reports can be organized in subdirectories
	'reportDir' => 'reports',

	//the root directory of all dashboards
	'dashboardDir' => 'dashboards',
	
	//the directory where things will be cached
	//this is relative to the project root by default, but can be set to an absolute path too
	//the cache has some relatively long lived data so don't use /tmp if you can avoid it
	//(for example historical report timing data is stored here)
	'cacheDir' => 'cache',

	//this maps file extensions to report types
	//to override this for a specific report, simply add a TYPE header
	//any file extension not in this array will be ignored when pulling the report list
	'default_file_extension_mapping' => array(
		'sql'=>'Mysql',
		'php'=>'Php',
		'js'=>'Mongo',
		'ado'=>'Ado',
	),
	
	//this enables listing different types of download formats on the report page
	//to change that one can add or remove any format from the list below
	//in order to create a divider a list entry have to be added with any key name and 
	//a value of 'divider'
	'report_formats' => array(
		'csv'=>'CSV',
		'xlsx'=>'Download Excel 2007',
		'xls'=>'Download Excel 97-2003',
		'text'=>'Text',
		'table'=>'Simple table',
		'raw data'=>'divider',
		'json'=>'JSON',
		'xml'=>'XML',
		'sql'=>'SQL INSERT command',
		'technical'=>'divider',
		'debug'=>'Debug information',
		'raw'=>'Raw report dump',
	),

	//this enebales one to change the default bootstrap theme
	'bootstrap_theme' => 'default',

	//this list all the available themes for a user to switch and use the one he or she likes
	//once removed the theme will not appear in the dropdown
	//if all to be removed - no dropdown will be visible for the user and the default (above) will be used
	'bootstrap_themelist' => array(
	    'default',
	    'amelia', 'cerulean', 'cosmo', 'cyborg', 'flatly', 'journal', 'readable', 'simplex', 'slate', 'spacelab', 'united'
	),
	
	//email settings
	'mail_settings' => array(
		//set 'enabled' to true to enable the 'email this report' functionality
		'enabled'=>true,
		
		'from'=>'sreekanthreddy.vasavi@gmail.com',
		
		//php's mail function
		// 'method'=>'mail'
		
		//sendmail
		
		// 'method'=>'sendmail',
		// 'command'=>'/usr/sbin/sendmail -bs' //optional
		
		
		//smtp
		
		'method'=>'smtp',
		'server'=>'smtp.gmail.com',
		'port'=>'587', 						//optional (default 25)
		
		'username'=>'', 	//optional
		'password'=>'', 	//optional
		'encryption'=>'tls' 				//optional (either 'ssl' or 'tls')
		
	),

	//email settings
	'mail_scheduler' => array(
		//set 'enabled' to true to enable the 'email this report' functionality
		'enabled'=>true,
		
		'from'=>'reports@reports.adrtr.net',
		
		//php's mail function
		// 'method'=>'mail'
		
		//sendmail
		
		// 'method'=>'sendmail',
		// 'command'=>'/usr/sbin/sendmail -bs' //optional
		
		
		//smtp
		
		'method'=>'smtp',
		'server'=>'localhost',
		'port'=>'25', 						//optional (default 25)
		/*
		'username'=>'youremailusername', 	//optional
		'password'=>'yoursmtppassword', 	//optional
		'encryption'=>'ssl' 				//optional (either 'ssl' or 'tls')
		*/
	),

	//this defines the database environments
	//the keys are the environment names (e.g. "dev", "production")
	//the values are arrays that contain connection info
	'environments' => array(
		'main'=>array(
			//this is what is used as the "host" macro within reports
			'host'=>'localhost',

			'ado'=>array(
				'uri'=>'mysql://username:password@localhost/database'
			),

			'mysql'=>array(
				'host'=>'',
				'user'=>'',
				'pass'=>'',
				'database'=>'',
			),
                        'mysql1'=>array(
				'host'=>'',
				'user'=>'',
				'pass'=>'',
				'database'=>'test',
			),


			'mongo'=>array(
				'host'=>'localhost',
				'port'=>'27017'
			),
		),
	),
);
?>
