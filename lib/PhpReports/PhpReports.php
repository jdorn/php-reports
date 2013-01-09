<?php
class PhpReports {
	public static $config;
	public static $request;
	public static $twig;
	
	public static $vars;
	
	private static $loader_cache;
	
	public static function init($config = 'config/config.php') {
		//set up our autoloader
		spl_autoload_register(array('PhpReports','loader'),true,true);

		if(!file_exists($config)) {
			throw new Exception("Cannot find config file");
		}
		
		$default_config = include('config/config.php.sample');
		$config = include($config);
	
		self::$config = array_merge($default_config, $config);
		
		self::$request = Flight::request();
		self::$request->base = 'http://'.rtrim($_SERVER['HTTP_HOST'].self::$request->base,'/');
		
		//the load order for templates is: "templates/local", "templates/default", "templates"
		//this means loading the template "html/report.twig" will load the local first and then the default
		//if you want to extend a default template from within a local template, you can do {% extends "default/html/report.twig" %} and it will fall back to the last loader
		$template_dirs = array('templates/default','templates');
		if(file_exists('templates/local')) array_unshift($template_dirs, 'templates/local');
		
		$loader = new Twig_Loader_Chain(array(
			new Twig_Loader_Filesystem($template_dirs), 
			new Twig_Loader_String()
		));
		self::$twig = new Twig_Environment($loader);
		
		self::$twig->addFunction('dbdate',new Twig_Function_Function('PhpReports::dbdate'));
		
		FileSystemCache::$cacheDir = self::$config['cacheDir'];

		if(!isset($_SESSION['environment']) || !isset(self::$config['environments'][$_SESSION['environment']])) {
			$_SESSION['environment'] = array_shift(array_keys(self::$config['environments']));
		}
	}
	
	public static function setVar($key,$value) {
		if(!self::$vars) self::$vars = array();
		
		self::$vars[$key] = $value;
	}
	public static function getVar($key, $default=null) {
		if(isset(self::$vars[$key])) return self::$vars[$key];
		else return $default;
	}
	
	public static function dbdate($time, $database=null, $format=null) {		
		$report = self::getVar('Report',null);
		if(!$report) return strtotime('Y-m-d H:i:s',strtotime($time));
		
		//if a variable name was passed in
		$var = null;
		if(isset($report->options['Variables'][$time])) {
			$var = $report->options['Variables'][$time];
			$time = $report->macros[$time];
		}
		
		$time = strtotime($time);
		
		$environment = $report->getEnvironment();
		
		//determine time offset
		$offset = 0;
		
		if($database) {
			if(isset($environment[$database]['time_offset'])) $offset = $environment[$database]['time_offset'];
		}
		else {
			$database = $report->getDatabase();
			if(isset($database['time_offset'])) $offset = $database['time_offset'];
		}
		
		//if the time needs to be adjusted
		if($offset) {
			$time = strtotime((($offset > 0)? '+' : '-').abs($offset).' hours',$time);
		}
		
		//determine output format
		if($format) {
			$time = date($format,$time);
		}
		elseif($var && isset($var['format'])) {
			$time = date($var['format'],$time);
		}
		//default to Y-m-d H:i:s
		else {
			$time = date('Y-m-d H:i:s',$time);
		}
		
		return $time;
	}
	
	public static function render($template, $macros) {		
		$default = array(
			'base'=>self::$request->base,
			'report_list_url'=>self::$request->base.'/',
			'request'=>self::$request,
			'querystring'=>$_SERVER['QUERY_STRING'],
			'config'=>self::$config,
			'environment'=>$_SESSION['environment']
		);
		$macros = array_merge($default,$macros);
		
		//if a template path like 'html/report' is given, add the twig file extension
		if(preg_match('/^[a-zA-Z_\-0-9\/]+$/',$template)) $template .= '.twig';
		return self::$twig->render($template,$macros);
	}
	
	public static function displayReport($report,$type) {		
		$classname = ucfirst(strtolower($type)).'ReportFormat';
		
		$error_header = 'An error occurred while running your report';
		$content = '';
		
		try {
			if(!class_exists($classname)) {
				$error_header = 'Unknown report format';
				throw new Exception("Unknown report format '$type'");
			}
			
			try {
				$report = $classname::prepareReport($report);
			}
			catch(Exception $e) {
				$error_header = 'An error occurred while preparing your report';
				throw $e;
			}
			
			$classname::display($report,self::$request);
			$content = $report->options['Query_Formatted'];
		}
		catch(Exception $e) {
			echo self::render('html/page',array(
				'title'=>$report->report,
				'header'=>'<h2>'.$error_header.'</h2>',
				'error'=>$e->getMessage(),
				'content'=>$content,
				'breadcrumb'=>array('Report List'=>'', $report->report => true)
			));
		}
	}
	
	public static function editReport($report) {
		$template_vars = array();
		
		try {
			$report = ReportFormatBase::prepareReport($report);
			
			$template_vars = array(
				'report'=>$report->report,
				'options'=>$report->options,
				'contents'=>$report->getRaw(),
				'extension'=>array_pop(explode('.',$report->report))
			);
		}
		//if there is an error parsing the report
		catch(Exception $e) {
			$template_vars = array(
				'report'=>$report,
				'contents'=>Report::getReportFileContents($report),
				'options'=>array(),
				'extension'=>array_pop(explode('.',$report)),
				'error'=>$e
			);
		}
		
		if(isset($_POST['preview'])) {
			echo "<pre>".SimpleDiff::htmlDiffSummary($template_vars['contents'],$_POST['contents'])."</pre>";
		}
		elseif(isset($_POST['save'])) {
			Report::setReportFileContents($template_vars['report'],$_POST['contents']);
		}
		else {
			echo self::render('html/report_editor',$template_vars);
		}
	}
	
	public static function listReports() {
		$errors = array();

		$reports = self::getReports(self::$config['reportDir'].'/',null,$errors);


		$template_vars['reports'] = $reports;
		$template_vars['report_errors'] = $errors;

		$start = microtime(true);
		echo self::render('html/report_list',$template_vars);
	}
	
	public static function getReportListJSON($reports=null) {
		if($reports === null) {
			$errors = array();
			$reports = self::getReports(self::$config['reportDir'].'/',null,$errors);
		}
		
		$parts = array();
		
		foreach($reports as $report) {
			if($report['is_dir'] && $report['children']) {
				//skip if the directory doesn't have a title
				if(!isset($report['Title']) || !$report['Title']) continue;
				
				$part = trim(self::getReportListJSON($report['children']),'[],');
				if($part) $parts[] = $part;
			}
			else {
				//skip if report is marked as dangerous
				if(isset($report['stop']) || isset($report['Caution']) || isset($report['warning'])) continue;
				
				$parts[] = json_encode(array(
					'name'=>$report['Name'],
					'url'=>$report['url']
				));
			}
		}
		
		return '['.trim(implode(',',$parts),',').']';
	}
	
	protected static function getReports($dir, $base = null, &$errors = null) {
		if($base === null) $base = $dir;
		$reports = glob($dir.'*',GLOB_NOSORT);
		$return = array();
		foreach($reports as $key=>$report) {
			$title = $description = false;
			
			if(is_dir($report)) {
				if(file_exists($report.'/TITLE.txt')) $title = file_get_contents($report.'/TITLE.txt');
				if(file_exists($report.'/README.txt')) $description = file_get_contents($report.'/README.txt');
				
				$id = str_replace(array('_','-','/',' '),array('','','_','-'),trim(substr($report,strlen($base)),'/'));
				
				$children = self::getReports($report.'/', $base, $errors);
				
				$count = 0;
				foreach($children as $child) {
					if(isset($child['count'])) $count += $child['count'];
					else $count++;
				}
				
				$return[] = array(
					'Name'=>ucwords(str_replace(array('_','-'),' ',basename($report))),
					'Title'=>$title,
					'Id'=> $id,
					'Description'=>$description,
					'is_dir'=>true,
					'children'=>$children,
					'count'=>$count
				);
			}
			else {
				//files to skip
				if(strpos(basename($report),'.') === false) continue;
				$ext = array_pop(explode('.',$report));
				if(!isset(self::$config['default_file_extension_mapping'][$ext])) continue;

				$cacheKey = FileSystemCache::generateCacheKey($report,'report_headers');

				//check if report data is cached and newer than when the report file was created
				//the url parameter ?nocache will bypass this and not use cache
				$data =false;
				if(!isset($_REQUEST['nocache'])) {
					$data = FileSystemCache::retrieve($cacheKey, filemtime($report));
				}
				
				//report data not cached, need to parse it
				if($data === false) {
					$name = substr($report,strlen($base));
					try {
						$temp = new Report($name);
					}
					catch(Exception $e) {
						if(!$errors) $errors = array();
						$errors[] = array(
							'report'=>$name,
							'exception'=>$e
						);
						continue;
					}
					
					$data = $temp->options;
					
					$data['report'] = $name;
					$data['url'] = self::$request->base.'/report/html/?report='.$name;
					$data['is_dir'] = false;
					$data['Id'] = str_replace(array('_','-','/',' ','.'),array('','','_','-','_'),trim(substr($report,strlen($base)),'/'));
					if(!isset($data['Name'])) $data['Name'] = ucwords(str_replace(array('_','-'),' ',basename($report)));
					
					//store parsed report in cache
					FileSystemCache::store($cacheKey, $data);
				}
				
				$return[] = $data;
			}
		}
		
		usort($return,function(&$a,&$b) {
			if($a['is_dir'] && !$b['is_dir']) return 1;
			elseif($b['is_dir'] && !$a['is_dir']) return -1;
			
			if(!$a['Title'] && !$b['Title']) return strcmp($a['Name'],$b['Name']);
			elseif(!$a['Title']) return 1;
			elseif(!$b['Title']) return -1;
			
			return strcmp($a['Title'], $b['Title']);
		});
		
		return $return;
	}
	
	/**
	 * Autoloader methods
	 */
	public static function loader($className) {		
		if(!isset(self::$loader_cache)) {
			self::buildLoaderCache();
		}
		
		if(isset(self::$loader_cache[$className])) {
			require_once(self::$loader_cache[$className]);
			return true;
		}
		else {
			return false;
		}
	}
	public static function buildLoaderCache() {
		self::load('classes/local');
		self::load('classes',array('classes/local'));
		self::load('lib');
	}
	public static function load($dir, $skip=array()) {
		$files = glob($dir.'/*.php');
		$dirs = glob($dir.'/*',GLOB_ONLYDIR);
		
		
		foreach($files as $file) {
			//for file names same as class name
			$className = basename($file,'.php');
			if(!isset(self::$loader_cache[$className])) self::$loader_cache[$className] = $file;
			
			//for PEAR style: Path_To_Class.php
			$parts = explode('/',substr($file,0,-4));
			array_shift($parts);
			$className = implode('_',$parts);
			//if any of the directories in the path are lowercase, it isn't in PEAR format
			if(preg_match('/(^|_)[a-z]/',$className)) continue;
			if(!isset(self::$loader_cache[$className])) self::$loader_cache[$className] = $file;
		}
		
		foreach($dirs as $dir2) {
			//directories to skip
			if($dir2[0]==='.') continue;
			if(in_array($dir2,$skip)) continue;
			if(in_array(basename($dir2),array('tests','test','example','examples','bin'))) continue;
			
			self::load($dir2,$skip);
		}
	}
	
	public static function json_decode($json, $assoc=false) {
		//replace single quoted values
		$json = preg_replace('/:\s*\'(([^\']|\\\\\')*)\'\s*([},])/e', "':'.json_encode(stripslashes('$1')).'$3'", $json);
		
		//replace single quoted keys
		$json = preg_replace('/\'(([^\']|\\\\\')*)\'\s*:/e', "json_encode(stripslashes('$1')).':'", $json);
		
		//remove any line breaks in the code
		$json = str_replace(array("\n","\r"),"",$json);
		
		//replace non-quoted keys with double quoted keys
		$json = preg_replace('/([{,])(\s*)([^"]+?)\s*:/','$1"$3":',$json);
		
		//remove trailing comma
		$json = preg_replace('/,\s*\}/','}',$json);
		
		return json_decode($json, $assoc);
	}
}
PhpReports::init();
