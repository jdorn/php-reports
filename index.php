<?php
require_once('lib/Mustache/Mustache.php');
require_once('lib/FileSystemCache/FileSystemCache.php');
require_once('lib/Class/Report.php');
require_once('config/config.php');

if(isset($cacheDir)) FileSystemCache::$cacheDir = $cacheDir;

function getReports($dir, $base = null) {
	if($base === null) $base = $dir;
	$reports = glob($dir.'*',GLOB_NOSORT);
	$return = array();
	foreach($reports as $key=>$report) {
		if(is_dir($report)) {
			$return[] = array(
				'Name'=>ucwords(str_replace(array('_','-'),' ',basename($report))),
				'is_dir'=>true,
				'children'=>getReports($report.'/', $base)
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
				$data['url'] = 'report.php?report='.$name;
				$data['is_dir'] = false;
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


if(!isset($reportDir)) $reportDir = 'reports';

$reports = getReports($reportDir.'/');

$m = new Mustache;

$template_file = file_get_contents('templates/report_list.html');
$content = $m->render($template_file,array('reports'=>$reports));

$page_template_file = file_get_contents('templates/page.html');
echo $m->render($page_template_file,array('content'=>$content,'title'=>'Report List'));
