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
	
	public static function csvReport($report) {
		$report = self::prepareReport($report);
		
		$page_template = array(
			'content'=>$report->renderReportPage('csv/report','csv/page')
		);
		
		$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);
		
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=".$file_name.".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		self::renderPage($page_template,'csv/page');
	}
	
	public static function textReport($report) {
		$report = self::prepareReport($report);
		
		$page_template = array(
			'content'=>$report->renderReportPage('text/report','text/page')
		);
		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");

		self::renderPage($page_template,'text/page');
	}
	
	public static function jsonReport($report) {
		$report = self::prepareReport($report);
		
		$page_template = array(
			'content'=>$report->renderReportPage('json/report','json/page')
		);
		
		header("Content-type: application/json");
		header("Pragma: no-cache");
		header("Expires: 0");

		self::renderPage($page_template,'json/page');
	}
	
	public static function sqlReport($report) {
		$report = self::prepareReport($report);
		
		$page_template = array(
			'content'=>$report->renderReportPage('sql/report','sql/page')
		);
		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		self::renderPage($page_template,'sql/page');
	}
	
	public static function xmlReport($report) {
		$report = self::prepareReport($report);
		
		$page_template = array(
			'content'=>$report->renderReportPage('xml/rows','xml/report')
		);
		
		header("Content-type: application/xml");
		header("Pragma: no-cache");
		header("Expires: 0");

		self::renderPage($page_template,'xml/page');
	}
	
	public static function htmlReport($report) {
		$report = self::prepareReport($report);
		
		$report->async = !isset($_REQUEST['content_only']);
		
		try {
			$page_template = array(
				'content'=>$report->renderReportPage('html/table','html/report'),
				'has_charts'=>$report->options['has_charts']
			);
		}
		catch(Exception $e) {
			$page_template = array(
				'error'=>$e->getMessage(),
				'content'=>$report->options['Query_Formatted']
			);
		}

		if(isset($_REQUEST['content_only'])) {
			self::renderPage($page_template,'text/page');
			exit;
		}
		else {
			self::renderPage($page_template,'html/page');
		}
	}
	
	public static function listReports() {
		$reports = self::getReports(self::$config['reportDir'].'/');
		
		$m = new Mustache;
		$template_file = file_get_contents('templates/html/report_list.mustache');
		$content = $m->render($template_file,array('reports'=>$reports));

		self::renderPage(array(
			'content'=>$content,
			'title'=>'Report List',
			'is_home'=>true
		));
	}
	
	
	
	protected static function prepareReport($report) {
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
		$report->options['csv_link'] = self::$request->base.'/report/csv/?'.$_SERVER['QUERY_STRING'];

		return $report;
	}	
	
	protected function renderPage($options, $page='html/page') {
		$default = array(
			'base'=>self::$request->base,
			'report_list_url'=>self::$request->base.'/'
		);
		
		$options = array_merge($options,$default);
		
		
		$page_template_file = file_get_contents('templates/'.$page.'.mustache');
		
		$m = new Mustache;
		echo $m->render($page_template_file,$options);
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
				//files to skip
				if(strpos(basename($report),'.') === false) continue;
				$ext = array_pop(explode('.',$report));
				if(!in_array($ext,array('sql','js'))) continue;
			
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
						echo "<div><strong>$name</strong> - ".$e->getMessage()."</div>";
						continue;
					}
					
					$data = $temp->options;
					
					$data['report'] = $name;
					$data['url'] = self::$request->base.'/report/html/?report='.$name;
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
	
	public static function loader($className) {				
		//first try to load from the classes directory
		if(self::load($className.'.php','classes')) return true;
		
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
