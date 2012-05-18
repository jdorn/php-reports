<?php
class Report {
	public $report;
	public $macros = array();
	public $options = array();
	public $is_ready = false;
	public $async = false;
	public $headers = array();
	public $raw_query;
	
	protected $mustache;
	
	protected $raw;
	protected $raw_headers;
	protected $filters = array();
	
	public function __construct($report,$macros = array(), $database = null) {
		$reportDir = PhpReports::$config['reportDir'];
		
		if(!file_exists($reportDir.'/'.$report)) {
			throw new Exception('Report not found - '.$report);
		}
		
		$this->report = $report;
		
		//instantiate the templating engine
		$this->mustache = new Mustache;
		
		//get the raw report file and convert EOL to unix style
		$this->raw = file_get_contents($reportDir.'/'.$this->report);
		$this->raw = str_replace(array("\r\n","\r"),"\n",$this->raw);
		
		//if there are no headers in this report
		if(strpos($this->raw,"\n\n") === false) {
			throw new Exception('Report missing headers - '.$report);
		}
		
		//split the raw report into headers and code
		list($this->raw_headers, $this->raw_query) = explode("\n\n",$this->raw,2);
		
		$this->macros = $macros;
		
		$this->parseHeader();
		
		$this->options['Database'] = $database;
		
		$this->initDb();
		
		$this->getTimeEstimate();
	}
	
	protected function parseHeader() {
		//default the report to being ready
		//if undefined variables are found in the headers, set to false
		$this->is_ready = true;
		
		$this->options = array(
			'Filters'=>array(),
			'Variables'=>array(),
			'Name'=>$this->report
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
			if(!in_array(substr($line,0,2),array('--','/*')) && $line[0] !== '#') continue;
			
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
				
				//compatibility with legacy system
				if(strtoupper($name) === $name) $name = ucfirst(strtolower($name));
			}			
			
			if($last_i === $i) {
				$last_header_value .= "\n".$value;
				$last_header = $name;
				continue;
			}
			elseif($last_header) {
				$classname = $last_header.'Header';
				if(class_exists($classname)) {
					$classname::parse($last_header,$last_header_value,$this);
					if(!in_array($last_header,$this->headers)) $this->headers[] = $last_header;
				}
				else {
					throw new Exception("Unknown header '$last_header' - ".$this->report);
				}
			}
				
			$last_header = $name;
			$last_header_value = $value;
			$last_i = $i;
		}
		
		//try to infer report type from file extension
		if(!isset($this->options['Type'])) {
			$file_type = array_pop(explode('.',$this->report));
			switch($file_type) {
				case 'js':
					$this->options['Type'] = 'Mongo';
					break;
				case 'sql':
					$this->options['Type'] = 'Mysql';
					break;
				case 'php':
					$this->options['Type'] = 'Php';
					break;
				default:
					throw new Exception("Unknown report type - ".$this->report);
			}
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
			$value = $classname::filter($value, $options);
			
			//if the column should not be displayed
			if($value === false) return false;
		}
		
		return $value;
	}
	
	protected function initDb() {
		$classname = $this->options['Type'].'ReportType';
		
		if(!class_exists($classname)) {
			throw new exception("Unknown report type '".$this->options['Type']."'");
		}
		
		$classname::init($this);
	}
	
	public function getRaw() {
		return $this->raw;
	}
	
	public function renderVariableForm($template='variable_form') {
		if(!file_exists('templates/html/'.$template.'.mustache')) {
			throw new Exception("Variable Form template now found");
		}
		
		if($this->options['Variables']) {
			$form = file_get_contents('templates/html/'.$template.'.mustache');
			
			$template_vars = array(
				'vars'=>array(),
				'database'=>$this->options['Database'],
				'databases'=>$this->options['Databases'],
				'report'=>$this->report
			);
			
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
				
				$template_vars['vars'][] = $params;
			}
			
			return $this->mustache->render($form, $template_vars);
		}
		else return '';
	}
	
	public function runReport() {
		if(!$this->is_ready) {
			throw new Exception("Report is not ready.  Missing variables");
		}		
		
		$classname = $this->options['Type'].'ReportType';
		
		if(!class_exists($classname)) {
			throw new exception("Unknown report type '".$this->options['Type']."'");
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
					'value'=>$value,
					'raw_value'=>$value
				);
				
				//apply filters for the column key
				$val = $this->applyFilters($key,$val);
				//apply filters for the column position
				if($val) $val = $this->applyFilters($i,$val);
				
				if($val) {
					$val['first'] = !$rowvals;
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
	
	public function renderReportContent($template='html/table') {
		$start = microtime(true);
		
		if($this->is_ready && !$this->async) {
			$this->runReport();
			$this->prepareRows();
		}
		
		//call the beforeRender callback for each header
		foreach($this->headers as $header) {
			$classname = $header.'Header';
			$classname::beforeRender($this);
		}
		
		if(!file_exists('templates/'.$template.'.mustache')) {
			throw new Exception("Report content template not found");
		}
		
		//get current report times for this report
		$report_times = FileSystemCache::retrieve($this->report,'report_times');
		if(!$report_times) $report_times = array();
		//only keep the last 10 times for each report
		//this keeps the timing data up to date and relevant
		if(count($report_times) > 10) array_shift($report_times);
		
		//store report times
		$this->options['Time'] = round(microtime(true) - $start,5);
		$report_times[] = $this->options['Time'];
		FileSystemCache::store($this->report, $report_times, 'report_times');
		
		$template_code = file_get_contents('templates/'.$template.'.mustache');
		
		$ret = $this->mustache->render($template_code, $this->options);
		return $ret;
	}
	
	public function renderReportPage($content_template='html/table',$report_template='html/report') {
		$variable_form = $this->renderVariableForm();
		$content = $this->renderReportContent($content_template);
		
		$template_vars = array(
			'is_ready'=>$this->is_ready,
			'async'=>$this->async,
			'report_url'=>PhpReports::$request->base.'/report/?'.$_SERVER['QUERY_STRING'],
			'base'=>PhpReports::$request->base,
			'content'=> $content,
			'variable_form'=>$variable_form
		);
		
		$template_vars = array_merge($template_vars,$this->options);
		
		if(!file_exists('templates/'.$report_template.'.mustache')) {
			throw new Exception("Report template not found");
		}
		$template_code = file_get_contents('templates/'.$report_template.'.mustache');
		
		return $this->mustache->render($template_code, $template_vars);
	}
}
?>
