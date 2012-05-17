<?php
class Report {
	public $report;
	public $template;
	public $macros;
	public $options;
	public $is_ready;
	public $async;
	public $headers;
	
	protected $mustache;
	
	protected $raw;
	protected $raw_headers;
	public $raw_query;
	
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
					$this->headers[] = $last_header;
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
					$this->options['Type'] = 'mongo';
					break;
				case 'sql':
					$this->options['Type'] = 'mysql';
					break;
				default:
					throw new Exception("Unknown report type - ".$this->report);
			}
		}
	}
	
	protected function initDb() {
		//set up database connections
		switch($this->options['Type']) {
			case 'mysql':
				$mysql_connections = PhpReports::$config['mysql_connections'];
			
				//if the database isn't set or doesn't exist, use the first defined one
				if(!$this->options['Database'] || !isset($mysql_connections[$this->options['Database']])) {
					$this->options['Database'] = current(array_keys($mysql_connections));
				}
				
				//set up list of all available databases for displaying form for switching between them
				$this->options['Databases'] = array();
				foreach(array_keys($mysql_connections) as $name) {
					$this->options['Databases'][] = array(
						'selected'=>($this->options['Database'] === $name),
						'name'=>$name
					);
				}
				break;
			case 'mongo':
				$mongo_connections = PhpReports::$config['mongo_connections'];
			
				//if the database isn't set or doesn't exist, use the first defined one
				if(!$this->options['Database'] || !isset($mongo_connections[$this->options['Database']])) {
					$this->options['Database'] = current(array_keys($mongo_connections));
				}
				
				//set up list of all available databases for displaying form for switching between them
				$this->options['Databases'] = array();
				foreach(array_keys($mongo_connections) as $name) {
					$this->options['Databases'][] = array(
						'selected'=>($this->options['Database'] == $name),
						'name'=>$name
					);
				}
				break;
			case 'default':
				throw new Exception("Unknown report type - ".$report);
		}
	}
	
	public function getRaw() {
		return $this->raw;
	}
	
	protected function openDb() {
		switch($this->options['Type']) {
			case 'mysql':
				$mysql_connections = PhpReports::$config['mysql_connections'];
				$config = $mysql_connections[$this->options['Database']];
				
				if(!($this->conn = mysql_connect($config['host'], $config['username'], $config['password']))) {
					throw new Exception('Could not connect to Mysql: '.mysql_error());
				}
				if(!mysql_select_db($config['database'])) {
					throw new Exception('Could not select Mysql database: '.mysql_error());
				}
				break;
		}
	}
	protected function closeDb() {
		if($this->options['Type'] === 'mysql') {
			mysql_close($this->conn);
		}
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
		$this->openDb();
		
		if(!$this->is_ready) {
			throw new Exception("Report is not ready.  Missing variables");
		}
		
		$rows = array();
		$start = microtime(true);
		
		if($this->options['Type'] === 'mysql') {
			$macros = $this->macros;
			foreach($macros as $key=>$value) {
				if(is_array($value)) {
					$first = true;
					foreach($value as $key2=>$value2) {
						$value[$key2] = array(
							'first'=>$first,
							'value'=>$value2
						);
						$first = false;
					}
					$macros[$key] = $value;
				}
			}
			
			//expand macros in query
			$sql = $this->mustache->render($this->raw_query,$macros);
			
			$this->options['Query'] = $sql;
			
			$this->options['Query_Formatted'] = SqlFormatter::highlight($sql);
			
			//split queries and run each one, saving the last result
			$queries = explode(';',$sql);
			foreach($queries as $query) {
				//skip empty queries
				$query = trim($query);
				if(!$query) continue;
				
				$result = mysql_query($query);
				if(!$result) {
					throw new Exception("Query failed: ".mysql_error());
				}
			}
			
			while($row = mysql_fetch_assoc($result)) {
				$rows[] = $row;
			}
		}
		elseif($options['Type'] === 'mongo') {	
			throw new Exception("Not implemented");
			
			$eval = '';
			foreach($this->macros as $key=>$value) {
				$eval .= 'var '.$key.' = "'.addslashes($value).'";';
			}
			$command = 'mongo '.$mongo_connections[$config]['host'].':'.$mongo_connections[$config]['port'].'/'.$options['Database'].' --quiet --eval "'.addslashes($eval).'" '.$report;
			echo $command;
			
			$options['Query'] = $command;
		}
		else {
			throw new Exception("Unknown report type");
		}
		
		$this->closeDb();
		
		$this->options['Time'] = round(microtime(true) - $start,5);
		$this->options['Count'] = count($rows);
		$this->options['Rows'] = $rows;
		
		//store the report time
		$report_times = FileSystemCache::retrieve($this->report,'report_times');
		if(!$report_times) $report_times = array();
		$report_times[] = $this->options['Time'];
		
		//only keep the last 10 times for each report
		//this keeps the timing data up to date and the cache small
		if(count($report_times) > 10) array_shift($report_times);
		
		FileSystemCache::store($this->report, $report_times, 'report_times');
	}
	
	protected function getTimeEstimate() {
		$report_times = FileSystemCache::retrieve($this->report,'report_times');
		if(!$report_times) return;
		
		$sum = array_sum($report_times);
		$count = count($report_times);
		$average = $sum/$count;
		
		$sample_square = 0;
		for($i = 0; $i < $count; $i++) {
			$sample_square += pow($report_times[$i], 2);
		}
		$standard_deviation = sqrt($sample_square / $count - pow(($average), 2));
		
		$this->options['time_estimate'] = round($average,2);
		$this->options['time_estimate_stdev'] = round($standard_deviation,2);
		$this->options['time_estimate_count'] = $count;
	}
	
	protected function prepareRows() {
		$rows = array();
		$chart_rows = array();
		
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
				$rowval[] = array(
					'key'=>$key,
					'key_collapsed'=>trim(preg_replace(array('/\s+/','/[^a-zA-Z0-9_]*/'),array('_',''),$key),'_'),
					'value'=>$value,
					'first'=>$i===1
				);
				$i++;
			}
			
			$first = !$rows;
			
			$row = array(
				'values'=>$rowval,
				'first'=>$first
			);
			
			foreach($this->headers as $header) {
				$classname = $header.'Header';
				$row = $classname::filterRow($row,$this);
				if(!$row) break;
			}
			
			if($row) $rows[] = $row;
		}
		
		$this->options['Rows'] = $rows;
	}
	
	public function renderReportContent($template='html/table') {
		if($this->is_ready && !$this->async) {
			$this->runReport();
			$this->prepareRows();
		}
		
		if(!file_exists('templates/'.$template.'.mustache')) {
			throw new Exception("Report content template not found");
		}
		
		$template_code = file_get_contents('templates/'.$template.'.mustache');
		return $this->mustache->render($template_code, $this->options);
	}
	
	public function renderReportPage($content_template='html/table',$report_template='html/report') {
		$template_vars = array(
			'is_ready'=>$this->is_ready,
			'async'=>$this->async,
			'report_url'=>PhpReports::$request->base.'/report/?'.$_SERVER['QUERY_STRING'],
			'base'=>PhpReports::$request->base,
			'content'=>$this->renderReportContent($content_template),
			'variable_form'=>$this->renderVariableForm()
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
