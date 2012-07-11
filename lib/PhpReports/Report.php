<?php
class Report {
	public $report;
	public $macros = array();
	public $exported_headers = array();
	public $options = array();
	public $is_ready = false;
	public $async = false;
	public $headers = array();
	public $header_lines = array();
	public $raw_query;
	public $use_cache;
	
	protected $raw;
	protected $raw_headers;
	protected $filters = array();
	protected $filemtime;
	protected $has_run = false;
	
	public function __construct($report,$macros = array(), $environment = null, $use_cache = null) {
		$reportDir = PhpReports::$config['reportDir'];
		
		if(!file_exists($reportDir.'/'.$report)) {
			throw new Exception('Report not found - '.$report);
		}
		
		$this->filemtime = filemtime($reportDir.'/'.$this->report);
		
		$this->report = $report;
		
		$this->use_cache = $use_cache;
		
		//get the raw report file and convert EOL to unix style
		$this->raw = file_get_contents($reportDir.'/'.$this->report);
		$this->raw = str_replace(array("\r\n","\r"),"\n",$this->raw);
		
		//if there are no headers in this report
		if(strpos($this->raw,"\n\n") === false) {
			throw new Exception('Report missing headers - '.$report);
		}
		
		//split the raw report into headers and code
		list($this->raw_headers, $this->raw_query) = explode("\n\n",$this->raw,2);
		
		$this->macros = array();
		foreach($macros as $key=>$value) {
			$this->addMacro($key,$value);
		}
		
		$this->parseHeaders();
		
		$this->options['Environment'] = $environment;
		
		$this->initDb();
		
		$this->getTimeEstimate();
	}
	
	public function addMacro($name, $value) {
		$this->macros[$name] = $value;
	}
	public function exportHeader($name,$params) {
		$this->exported_headers[] = array('name'=>$name,'params'=>$params);
	}
	
	public function getCacheKey() {
		return md5(serialize(array(
			'report'=>$this->report,
			'macros'=>$this->macros,
			'database'=>$this->options['Environment']
		)));
	}

	protected function retrieveFromCache() {
		if(!$this->use_cache) {
			return false;
		}
		
		return FileSystemCache::retrieve($this->getCacheKey(),'results', $this->filemtime);
	}
	
	protected function storeInCache() {
		if(isset($this->options['Cache']) && is_numeric($this->options['Cache'])) {
			$ttl = intval($this->options['Cache']);
		}
		else {
			//default to caching things for 10 minutes
			$ttl = 600;
		}
		
		FileSystemCache::store($this->getCacheKey(), $this->options, 'results', $ttl);
	}
	
	protected function parseHeaders() {
		//default the report to being ready
		//if undefined variables are found in the headers, set to false
		$this->is_ready = true;
		
		$this->options = array(
			'Filters'=>array(),
			'Variables'=>array(),
			'Includes'=>array(),
		);
		$this->headers = array();
		
		$lines = explode("\n",$this->raw_headers);
		$lines[] = '#END:';
		
		$last_header = null;
		$last_header_value = '';
		$last_i = -1;
		$i=0;
		foreach($lines as $line) {
			if(empty($line)) continue;
			
			//if the line doesn't start with a comment character, skip
			if(!in_array(substr($line,0,2),array('--','/*','//')) && $line[0] !== '#') continue;
			
			//remove comment from start of line and skip if empty
			$line = trim(ltrim($line,'-*/#'));
			if(!$line) continue;
			
			$has_name_value = preg_match('/^\s*[A-Z0-9_\-]+\s*\:/',$line);
			
			//if this is the first header and not in the format name:value, assume it is the report name
			if(!$last_header && !$has_name_value) {
				$name = 'Name';
				$value = trim($line);
			}
			else {
				if(!$has_name_value) {		
					$value = trim($line);
					
					if($last_header !== 'Name') $name = $last_header;
					else {
						$name = 'Description';
						$i++;
					}
				}
				else {
					$i++;
					list($name,$value) = explode(':',$line,2);
				}
				$name = trim($name);
				$value = trim($value);
				
				if(strtoupper($name) === $name) $name = ucfirst(strtolower($name));
			}			
			
			if($last_i === $i) {
				$last_header_value .= "\n".$value;
				$last_header = $name;
				continue;
			}
			elseif($last_header) {
				
				$this->header_lines[] = array(
					'name'=>$last_header,
					'value'=>$last_header_value
				);
				
				if(!in_array($last_header,$this->headers)) $this->headers[] = $last_header;
				
				$this->parseHeader($last_header,$last_header_value);
			}
				
			$last_header = $name;
			$last_header_value = $value;
			$last_i = $i;
		}
		
		//try to infer report type from file extension
		if(!isset($this->options['Type'])) {
			$file_type = array_pop(explode('.',$this->report));
			
			if(!isset(PhpReports::$config['default_file_extension_mapping'][$file_type])) {
				throw new Exception("Unknown report type - ".$this->report);
			}
			else {
				$this->options['Type'] = PhpReports::$config['default_file_extension_mapping'][$file_type];
			}
		}
		
		if(!isset($this->options['Database'])) $this->options['Database'] = strtolower($this->options['Type']);
		
		if(!isset($this->options['Name'])) $this->options['Name'] = $this->report;
	}
	
	public function parseHeader($name,$value) {
		$classname = $name.'Header';
		if(class_exists($classname)) {
			$classname::parse($name,$value,$this);
		}
		else {
			throw new Exception("Unknown header '$name' - ".$this->report);
		}
	}
	
	public function addFilter($column, $type, $options) {
		if(!isset($this->filters[$column])) $this->filters[$column] = array();
		
		$this->filters[$column][$type] = $options;
	}
	protected function applyFilters($column, $value) {
		//no filters to apply
		if(!isset($this->filters[$column])) return $value;
		
		foreach($this->filters[$column] as $type=>$options) {
			$classname = $type.'Filter';
			$value = $classname::filter($value, $options, $this);
			
			//if the column should not be displayed
			if($value === false) return false;
		}
		
		return $value;
	}
	
	protected function initDb() {
		//if the database isn't set, use the first defined one from config
		$environments = PhpReports::$config['environments'];
		if(!$this->options['Environment']) {
			$this->options['Environment'] = current(array_keys($environments));
		}
		
		//set database options
		$environment_options = array();
		foreach($environments as $key=>$params) {
			$environment_options[] = array(
				'name'=>$key,
				'selected'=>$key===$this->options['Environment']
			);
		}
		$this->options['Environments'] = $environment_options;
		
		//add a host macro
		if(isset($environments[$this->options['Environment']]['host'])) {
			$this->macros['host'] = $environments[$this->options['Environment']]['host'];
		}
		
		$classname = $this->options['Type'].'ReportType';
		
		if(!class_exists($classname)) {
			throw new exception("Unknown report type '".$this->options['Type']."'");
		}
		
		$classname::init($this);
	}
	
	public function getRaw() {
		return $this->raw;
	}
	
	public function prepareVariableForm() {
		$vars = array();
		
		if($this->options['Variables']) {
			foreach($this->options['Variables'] as $var => $params) {
				if(!isset($params['name'])) $params['name'] = ucwords(str_replace(array('_','-'),' ',$var));
				if(!isset($params['type'])) $params['type'] = 'string';
				if(!isset($params['options'])) $params['options'] = false;
				$params['value'] = $this->macros[$var];
				$params['key'] = $var;
				
				if($params['type'] === 'select') {
					$params['is_select'] = true;
					
					foreach($params['options'] as $key=>$option) {
						if(!is_array($option)) {
							$params['options'][$key] = array(
								'display'=>$option,
								'value'=>$option
							);
						}
						if($params['options'][$key]['value'] == $params['value']) $params['options'][$key]['selected'] = true;
						else $params['options'][$key]['selected'] = false;
						
						if($params['multiple']) {
							$params['is_multiselect'] = true;
							$params['choices'] = count($params['options']);
						}
					}
				}
				else {
					if($params['multiple']) {
						$params['is_textarea'] = true;
					}
				}
				
				$vars[] = $params;
			}	
		}
		
		return $vars;
	}
	
	protected function _runReport() {
		if(!$this->is_ready) {
			throw new Exception("Report is not ready.  Missing variables");
		}		
		
		//release the write lock on the session file
		//so the session isn't locked while the report is running
		session_write_close();
		
		$classname = $this->options['Type'].'ReportType';
		
		if(!class_exists($classname)) {
			throw new exception("Unknown report type '".$this->options['Type']."'");
		}
		
		foreach($this->headers as $header) {
			$headerclass = $header.'Header';
			$headerclass::beforeRun($this);
		}
		
		$classname::openConnection($this);
		$rows = $classname::run($this);
		$classname::closeConnection($this);
		
		$this->options['Count'] = count($rows);
		$this->options['Rows'] = $rows;
	}
	
	protected function getTimeEstimate() {
		$report_times = FileSystemCache::retrieve($this->report,'report_times');
		if(!$report_times) return;
		
		sort($report_times);
		
		$sum = array_sum($report_times);
		$count = count($report_times);
		$average = $sum/$count;
		$quartile1 = $report_times[round(($count-1)/4)];
		$median = $report_times[round(($count-1)/2)];
		$quartile3 = $report_times[round(($count-1)*3/4)];
		$min = min($report_times);
		$max = max($report_times);
		$iqr = $quartile3-$quartile1;
		$range = (1.5)*$iqr;
		
		$sample_square = 0;
		for($i = 0; $i < $count; $i++) {
			$sample_square += pow($report_times[$i], 2);
		}
		$standard_deviation = sqrt($sample_square / $count - pow(($average), 2));
		
		$this->options['time_estimate'] = array(
			'times'=>$report_times,
			'count'=>$count,
			'min'=>round($min,2),
			'max'=>round($max,2),
			'median'=>round($median,2),
			'average'=>round($average,2),
			'q1'=>round($quartile1,2),
			'q3'=>round($quartile3,2),
			'iqr'=>round($range,2),
			'sum'=>round($sum,2),
			'stdev'=>round($standard_deviation,2)
		);
	}
	
	protected function prepareRows() {
		$rows = array();
		
		//generate list of all values for each numeric column
		//this is used to calculate percentiles/averages/etc.
		$vals = array();
		foreach($this->options['Rows'] as $row) {
			foreach($row as $key=>$value) {
				if(!isset($vals[$key])) $vals[$key] = array();
				
				if(is_numeric($value)) $vals[$key][] = $value;
			}
		}
		$this->options['Values'] = $vals;
		
		foreach($this->options['Rows'] as $row) {
			$rowval = array();
			
			$i=1;
			foreach($row as $key=>$value) {						
				$val = array(
					'key'=>$key,
					'key_collapsed'=>trim(preg_replace(array('/\s+/','/[^a-zA-Z0-9_]*/'),array('_',''),$key),'_'),
					'value'=>utf8_encode($value),
					'raw_value'=>($value)
				);
				
				//apply filters for the column key
				$val = $this->applyFilters($key,$val);
				//apply filters for the column position
				if($val) $val = $this->applyFilters($i,$val);
				
				if($val) {
					$val['first'] = !$rowval;
					$rowval[] = $val;
				}
				
				$i++;
			}
			
			$first = !$rows;
			
			$row = array(
				'values'=>$rowval,
				'first'=>$first
			);
			
			if($row) $rows[] = $row;
		}
		
		$this->options['Rows'] = $rows;
	}
	
	public function run() {
		if($this->has_run) return true;
		
		//at this point, all the headers are parsed and we haven't run the report yet
		foreach($this->headers as $header) {
			$classname = $header.'Header';
			$classname::afterParse($this);
		}
		
		//record how long it takes to run the report
		$start = microtime(true);
		
		if($this->is_ready && !$this->async) {
			//if the report is cached
			if($options = $this->retrieveFromCache()) {				
				$this->options = $options;
				$this->options['FromCache'] = true;
			}
			else {
				$this->_runReport();
				$this->prepareRows();
				$this->storeInCache();
			}
		}
		
		//call the beforeRender callback for each header
		foreach($this->headers as $header) {
			$classname = $header.'Header';
			$classname::beforeRender($this);
		}
		
		$this->options['Time'] = round(microtime(true) - $start,5);
		
		if($this->is_ready && !$this->async && !isset($this->options['FromCache'])) {
			//get current report times for this report
			$report_times = FileSystemCache::retrieve($this->report,'report_times');
			if(!$report_times) $report_times = array();
			//only keep the last 10 times for each report
			//this keeps the timing data up to date and relevant
			if(count($report_times) > 10) array_shift($report_times);
			
			//store report times
			$report_times[] = $this->options['Time'];
			FileSystemCache::store($this->report, $report_times, 'report_times');
		}
		
		$this->has_run = true;
	}
	
	public function renderReportPage($template='html/report') {
		$this->run();
		
		$template_vars = array(
			'is_ready'=>$this->is_ready,
			'async'=>$this->async,
			'report_url'=>PhpReports::$request->base.'/report/?'.$_SERVER['QUERY_STRING'],
			'report_querystring'=>$_SERVER['QUERY_STRING'],
			'base'=>PhpReports::$request->base,
			'report'=>$this->report,
			'vars'=>$this->prepareVariableForm()
		);
		
		$template_vars = array_merge($template_vars,$this->options);
		
		return PhpReports::render($template, $template_vars);
	}
}
?>
