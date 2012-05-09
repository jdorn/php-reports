<?php
class Report {
	public $report;
	public $template;
	public $macros;
	public $options;
	public $is_ready;
	
	protected $mustache;
	
	protected $raw;
	protected $raw_headers;
	protected $raw_query;
	
	public function __construct($report,$macros = array(), $database = null) {
		if(!file_exists('reports/'.$report)) {
			throw new Exception('Report not found');
		}
		
		$this->report = $report;
		
		//instantiate the templating engine
		require_once('lib/Mustache/Mustache.php');
		$this->mustache = new Mustache;
		
		//get the raw report file and convert EOL to unix style
		$this->raw = file_get_contents('reports/'.$this->report);
		$this->raw = str_replace(array("\r\n","\r"),"\n",$this->raw);
		
		//split the raw report into headers and code
		list($this->raw_headers, $this->raw_query) = explode("\n\n",$this->raw,2);
		
		$this->macros = $macros;
		
		$this->parseHeader();
		
		$this->options['Database'] = $database;
		
		$this->initDb();
	}
	
	protected function parseHeader() {
		$this->options = array(
			'Variables'=>array(),
			'Filters'=>array()
		);
		
		//default the report to being ready
		//if undefined variables are found in the headers, set to false
		$this->is_ready = true;
		
		$lines = explode("\n",$this->raw_headers);
		
		$first = true;
		foreach($lines as $line) {
			//if the line doesn't start with a comment character, skip
			if(!in_array(substr($line,0,2),array('--','/*')) && $line[0] !== '#') continue;
			
			//remove comment from start of line and skip if empty
			$line = trim(ltrim($line,'-*/#'));
			if(!$line) continue;
			
			//if this is the first header and not in the format name:value, assume it is the report name
			if($first && strpos($line,':') === false) {
				$name = 'Name';
				$value = trim($line);
			}
			//otherwise skip if not in name:value format
			elseif(strpos($line,':')===false) {
				continue;
			}
			else {
				list($name,$value) = explode(':',$line,2);
				$name = trim($name);
				$value = trim($value);
				
				//make all cap names sentence case
				if(strtoupper($name) === $name) $name = ucfirst(strtolower($name));
			}
			
			$first = false;
			
			//this is a variable
			if($name === 'Variable') {
				list($var,$params) = explode(',',$value,2);
				$var = trim($var);
				$params = trim($params);
				
				if($temp = json_decode($params,true)) {
					$params = $temp;
				}
				else {
					$params = array(
						'name'=>$params
					);
				}
				
				//add to options
				$this->options['Variables'][$var] = $params;
				
				if(!isset($this->macros[$var]) && isset($params['default'])) {
					$this->macros[$var] = $params['default'];
				}
				elseif(!isset($this->macros[$var])) {
					$this->macros[$var] = '';
				}
				
				//if the macro value is empty and empty isn't allowed
				if(trim($this->macros[$var])==='' && (!isset($params['empty']) || !$params['empty'])) {
					$this->is_ready =false;
				}
			}
			//this is a filter
			elseif($name === 'Filter') {
				list($col,$params) = explode(',',$value,2);
				$col = trim($col);
				$params = trim($params);
				
				if($temp = json_decode($params,true)) {
					$params = $temp;
				}
				else {
					$params = array(
						'filter'=>$params
					);
				}
				
				$this->options['Filters'][$col] = $params;
			}			
			//this is another option
			else {
				if($temp = json_decode($value,true)) {
					$value = $temp;
				}
				
				$this->options[$name] = $value;
			}
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
					throw new Exception("Unknown report type");
			}
		}
		
		if(isset($this->options['Columns']) && !is_array($this->options['Columns'])) {
			$this->options['Columns'] = explode(',',$this->options['Columns']);
		}
	}
	
	protected function initDb() {
		require_once('config/config.php');
		
		//set up database connections
		switch($this->options['Type']) {
			case 'mysql':
				//if the database isn't set or doesn't exist, use the first defined one
				if(!$this->options['Database'] || !isset($mysql_connections[$this->options['Database']])) {
					$this->options['Database'] = current(array_keys($mysql_connections));
				}
				
				$config = $mysql_connections[$this->options['Database']];
				
				if(!mysql_connect($config['host'], $config['username'], $config['password'])) {
					throw new Exception('Could not connect to Mysql');
				}
				if(!mysql_select_db($config['database'])) {
					throw new Exception('Could not select Mysql database');
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
				throw new Exception("Unknown report type");
		}
	}
	
	public function renderVariableForm($template='variable_form') {
		if(!file_exists('templates/'.$template.'.html')) {
			throw new Exception("Variable Form template now found");
		}
		
		if($this->options['Variables']) {
			$form = file_get_contents('templates/'.$template.'.html');
			
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
		
		$rows = array();
		$start = microtime(true);
		
		if($this->options['Type'] === 'mysql') {
			//expand macros in query
			$sql = $this->mustache->render($this->raw_query,$this->macros);
			
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
			
			$this->options['Query'] = $sql;
			
			require_once('lib/SqlFormatter/SqlFormatter.php');
			$this->options['Query_Formatted'] = SqlFormatter::format($sql);
			
			while($row = mysql_fetch_assoc($result)) {
				$rows[] = $row;
			}
			
			$this->options['Time'] = round(microtime(true) - $start,5);
			$this->options['Count'] = count($rows);
			$this->options['Rows'] = $rows;
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
	}
	
	protected function applyFilters() {
		$rows = array();
		
		foreach($this->options['Rows'] as $row) {
			$rowval = array();
			$i=1;
			foreach($row as $key=>$value) {
				//get filter fot column
				if(isset($this->options['Filters'][$key])) {
					$filter = $this->options['Filters'][$key]['filter'];
				}
				elseif(isset($this->options['Filters'][$i]['filter'])) {
					$filter = $this->options['Filters'][$i]['filter'];
				}
				else {
					$filter = false;
				}
				
				//get class for column
				if(isset($this->options['Columns'][$i-1])) {
					$class = $this->options['Columns'][$i-1];
				}
				else {
					$class = false;
				}
				
				$alt = '';
				switch($filter) {
					case 'geoip':
						$record = @geoip_record_by_name($value);
						if($record) {
							$alt = $value;
							$value = $record['city'];
							if($record['country_code'] !== 'US') {
								$value .= ' '.$record['country_name'];
							}
							else {
								$value .= ', '.$record['region'];
							}
						}
						break;
				}
				
				$rowval[] = array('key'=>$key,'value'=>$value, 'alt'=>$alt, 'class'=>$class, 'first'=>$i===1);
				$i++;
			}
			
			$first = !$rows;
			$rows[] = array(
				'values'=>$rowval,
				'first'=>$first
			);
		}
		
		$this->options['Rows'] = $rows;
	}
	
	public function renderReport() {
		$this->runReport();
		$this->applyFilters();
		
		if(isset($this->options['Template'])) $template = $this->options['Template'];
		else $template = 'table';
		
		if(!file_exists('templates/'.$template.'.html')) {
			throw new Exception("Report template not found");
		}
		
		$template_code = file_get_contents('templates/'.$template.'.html');
		
		//print_r($this->options);
		
		return $this->mustache->render($template_code, $this->options);
	}
}
?>
