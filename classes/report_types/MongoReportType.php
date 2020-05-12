<?php
class MongoReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." database defined for environment '".$report->options['Environment']."'");
		}
		
		$mongo = $environments[$report->options['Environment']][$report->options['Database']];
		
		//default host macro to mysql's host if it isn't defined elsewhere
		if(!isset($report->macros['host']) && isset($mongo['host'])) $report->macros['host'] = $mongo['host'];
		
		//if there are any included reports, add it to the top of the raw query
		if(isset($report->options['Includes'])) {
			$included_code = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_code .= trim($included_report->raw_query)."\n";
			}
			
			$report->raw_query = $included_code . $report->raw_query;
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = '';
		foreach($report->macros as $key=>$value) {
			if(is_array($value)) {
				$value = json_encode($value);
			}
			else {
				$value = '"'.addslashes($value).'"';
			}
			
			$eval .= 'var '.$key.' = '.$value.';'."\n";
		}
		$eval .= $report->raw_query;

		$environments = PhpReports::$config['environments'];
		$config = $environments[$report->options['Environment']][$report->options['Database']];
		
		$mongo_database = isset($report->options['Mongodatabase'])? $report->options['Mongodatabase'] : '';

		// Get connection string
        $connectionString = '';
        if(isset($config['dsn'])) {
            // Replace any existing mongo database name with the one for the report
            $connectionString = preg_replace(
                '/(mongodb[a-zA-Z+]*:\/\/)?([^\/?]+)(\/[^\.,:?]*)?($|\?)/',
                '$1$2/'.$mongo_database.'$4',
                $config['dsn'],
                1
            );
        }
        elseif(isset($config['host'])) {
            $connectionString = $config['host'].':'.(isset($config['port'])?$config['port']:27017).'/'.$mongo_database;
        }

        //command without eval string
		$command = 'mongo '.escapeshellarg($connectionString).' --quiet --eval ';

        // Remove password from command for debug info
        $commandSanitized = preg_replace('/\/\/([^@:]+)(:[^:@]+)@/','//$1:*******@',$command);

		//easy to read formatted query
		$report->options['Query_Formatted'] = '<div>
			<pre style="background-color: black; color: white; padding: 10px 5px;">$ '.$commandSanitized.'"..."</pre>'.
			'Eval String:'.
			'<pre class="prettyprint linenums lang-js">'.htmlentities($eval).'</pre>
		</div>';

		//escape the eval string and add it to the command
		$command .= escapeshellarg($eval);
		$report->options['Query'] = '$ '.$commandSanitized.escapeshellarg($eval);

        $process = proc_open($command, [
            0 => ["pipe","r"],
            1 => ["pipe","w"],
            2 => ["pipe","w"]
        ], $pipes);

        $result = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);

        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        // Annoying bug in MongoDB causes some debug messages to be output to stdout and also ignore "--quiet"
        // Filter out anything that matches the debug pattern and move to $err
        $debugInfoRegex = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T.*/m';
        if(preg_match_all($debugInfoRegex, $result, $matches)) {
            $result = trim(preg_replace($debugInfoRegex, '', $result));
            $err .= "\n".implode("\n",$matches[0]);
        }

        if($exitCode > 0) {
            throw new Exception('Exit code: '.$exitCode."\n\n".$result."\n".$err);
        }
		
		$json = json_decode($result, true);
		if($json === NULL) throw new Exception('Exit code: '.$exitCode."\n\n".$result."\n".$err);
		
		return $json;
	}
}
