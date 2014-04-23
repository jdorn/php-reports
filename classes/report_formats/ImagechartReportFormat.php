<?php
class ImagechartReportFormat extends ReportFormatBase {
    public static function display(&$report, &$request) {
        if(!$report->options['has_charts']) return;
        
        $cachekey = FileSystemCache::generateCacheKey($report->getCacheKeyParameters(),'imagecharts');
        
        $result = FileSystemCache::retrieve($cachekey);
        if(!$result) {
            // Generate phantomjs command
            $pwd = trim(shell_exec('pwd'));
            $cmd = "phantomjs ".$pwd."/lib/phantomjs/chart.js";
            $cmd .= " ".escapeshellarg(PhpReports::$request->base);
            $cmd .= " ".escapeshellarg($report->report);
            $cmd .= " ".escapeshellarg($_SERVER['QUERY_STRING']);
            
            // Determine viewport width/height
            $fullwidth = isset($_REQUEST['width'])? $_REQUEST['width'] : 1024;
            $width = 0;
            $height = 0;
            foreach($report->options['Charts'] as $chart) {
                if(preg_match('/\%$/',$chart['width'])) $width = max($width,substr($chart['width'],0,-1)/100*$fullwidth);
                else $width = max($width,preg_replace('/^([0-9]+)([^0-9].*)?$/','$1',$chart['width']));
                $height += preg_replace('/^([0-9]+)([^0-9].*)?$/','$1',$chart['height'])*1;
            }
            $cmd .= " ".floor($width)." ".floor($height);
            
            // Generate temporary filename in cache directory
            if(!file_exists($pwd.'/cache/phatomjs/')) mkdir($pwd.'/cache/phantomjs/',0777,true);
            $filename = $pwd.'/cache/phantomjs/'.preg_replace('/\//','.',$report->report).'.'.date('Y-m-d_H-i-s').'.png';
            $cmd .= " ".escapeshellarg($filename);
            
            // Take the screenshot with phantomjs
            shell_exec($cmd);
            
            // Trim transparent border and whitespace
            shell_exec('mogrify -trim +repage -trim +repage '.$filename);
            
            // Cache result and delete image file
            $result = file_get_contents($filename);
            unlink($filename);
            FileSystemCache::store($cachekey, base64_encode($result), 600);
        }
        else {
            $result = base64_decode($result);
        }
        
        // Output image
        header('Content-Type: image/png');
        echo $result;
    }
}
