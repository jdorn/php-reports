<?php
class PhpReports {
	public static $config;
	public static $request;
	
	public static function init($config = 'config/config.php') {
		spl_autoload_register(array('PhpReports','loader'));
		
		if(!file_exists($config)) {
			throw new Exception("Cannot find config file");
		}
		
		self::$config = include($config);
		self::$request = Flight::request();
		
		FileSystemCache::$cacheDir = self::$config['cacheDir'];
	}
	
	public static function displayReport($report) {
		$database = null;
		if(isset($_REQUEST['database'])) {
			$database = $_REQUEST['database'];
			
			//store this database selection in the session
			$_SESSION['database'] = $database;
		}
		elseif(isset($_SESSION['database'])) {
			$database = $_SESSION['database'];
		}

		$macros = array();
		if(isset($_GET['macro_names'])) {
			$macros = array_combine($_GET['macro_names'],$_GET['macro_values']);
		}

		$report = new Report($report,$macros,$database);

		//add csv download link to report
		$report->options['csv_link'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&csv=true';

		//if exporting to csv, use the csv template instead of the report template
		if(isset($_REQUEST['csv'])) $report->options['Template'] = 'csv';

		$page_template = array();

		$page_template['title'] = $report->options['Name'];
		$page_template['variable_form'] = $report->renderVariableForm();

		if(isset($_REQUEST['raw'])) {
			$page_template['content'] = '<pre>'.$report->getRaw().'</pre>';
		}

		if(!$report->is_ready) {
			$page_template['notice'] = "The report needs more information before running.";
		}
		else {
			try {
				$page_template['report'] = $report->renderReport();
			}
			catch(Exception $e) {
				$page_template['error'] = $e->getMessage();
				if(isset($report->options['Query_Formatted'])) $page_template['content'] = $report->options['Query_Formatted'];
			}
		}

		if(isset($_REQUEST['csv'])) {
			$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);
			
			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename=".$file_name.".csv");
			header("Pragma: no-cache");
			header("Expires: 0");

			$page_template_file = 'csv_page';
		}
		else {	
			$page_template_file = 'page';
		}

		self::renderPage($page_template, $page_template_file);
	}
	
	protected static function getReports($dir, $base = null) {
		if($base === null) $base = $dir;
		$reports = glob($dir.'*',GLOB_NOSORT);
		$return = array();
		foreach($reports as $key=>$report) {
			$title = $description = false;
			
			if(is_dir($report)) {
				if(file_exists($report.'/TITLE.txt')) $title = file_get_contents($report.'/TITLE.txt');
				if(file_exists($report.'/README.txt')) $description = file_get_contents($report.'/README.txt');
				
				$id = str_replace(array('_','-','/',' '),array('','','_','-'),trim(substr($report,strlen($base)),'/'));
				
				$return[] = array(
					'Name'=>ucwords(str_replace(array('_','-'),' ',basename($report))),
					'Title'=>$title,
					'Id'=> $id,
					'Description'=>$description,
					'is_dir'=>true,
					'children'=>self::getReports($report.'/', $base)
				);
			}
			else {
				//check if report data is cached and newer than when the report file was created
				//the url parameter ?nocache will bypass this and not use cache
				$data =false;
				if(!isset($_REQUEST['nocache'])) {
					$data = FileSystemCache::retrieve($report, filemtime($report));
				}
				
				//report data not cached, need to parse it
				if($data === false) {
					$name = substr($report,strlen($base));
					try {
						$temp = new Report($name);
					}
					catch(Exception $e) {
						continue;
					}
					
					$data = $temp->options;
					
					$data['report'] = $name;
					$data['url'] = self::$request->base.'/report/?report='.$name;
					$data['is_dir'] = false;
					$data['Id'] = false;
					if(!isset($data['Name'])) $data['Name'] = ucwords(str_replace(array('_','-'),' ',basename($report)));
					
					//store parsed report in cache
					FileSystemCache::store($report, $data);
				}
				
				$return[] = $data;
			}
		}
		
		usort($return,function(&$a,&$b) {
			if($a['is_dir'] && !$b['is_dir']) return 1;
			elseif($b['is_dir'] && !$a['is_dir']) return -1;
			
			return strcmp($a['Name'], $b['Name']);
		});
		
		return $return;
	}
	
	public function renderPage($options, $page='page') {
		$default = array(
			'base'=>self::$request->base,
			'report_list_url'=>self::$request->base.'/'
		);
		
		$options = array_merge($options,$default);
		
		
		$page_template_file = file_get_contents('templates/'.$page.'.html');
		
		$m = new Mustache;
		echo $m->render($page_template_file,$options);
	}
	
	public static function listReports() {
		$reports = self::getReports(self::$config['reportDir'].'/');
		
		$m = new Mustache;
		$template_file = file_get_contents('templates/report_list.html');
		$content = $m->render($template_file,array('reports'=>$reports));

		self::renderPage(array(
			'content'=>$content,
			'title'=>'Report List',
			'is_home'=>true
		));
	}
	
	public static function loader($className) {
		if(file_exists('filters/'.$className.'.php')) {
			require('filters/'.$className.'.php');
			return true;
		}
		
		return self::load($className.'.php','lib');
	}
	public static function load($file, $dir) {
		if(file_exists($dir.'/'.$file)) {
			require($dir.'/'.$file);
			return true;
		}
		
		$dirs = glob($dir.'/*',GLOB_ONLYDIR);
		foreach($dirs as $dir) {
			if($dir[0] === '.') continue;
			if(self::load($file,$dir)) return true;
		}
		
		return false;
	}
}
PhpReports::init();
