<?php

/*
	Paul's Simple Diff Algorithm v 0.1
	(C) Paul Butler 2007 <http://www.paulbutler.org/>
	May be used and distributed under the zlib/libpng license.
	
	This code is intended for learning purposes; it was written with short
	code taking priority over performance. It could be used in a practical
	application, but there are a few ways it could be optimized.
	
	Given two arrays, the function diff will return an array of the changes.
	I won't describe the format of the array, but it will be obvious
	if you use print_r() on the result of a diff on some test data.
	
	htmlDiff is a wrapper for the diff command, it takes two strings and
	returns the differences in HTML. The tags used are <ins> and <del>,
	which can easily be styled with CSS.  
*/

class SimpleDiff {
	function diff($old, $new){
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}	
		}
		if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
		return array_merge(
			self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
		);
	}

	function htmlDiff($old, $new){
		$ret = '';
		$diff = self::diff(explode(" ", $old), explode(" ", $new));
		foreach($diff as $k){
			if(is_array($k))
				$ret .= (!empty($k['d'])?"<del>".implode(" ",array_map('htmlentities',$k['d']))."</del> ":'').
					(!empty($k['i'])?"<ins>".implode(" ",array_map('htmlentities',$k['i']))."</ins> ":'');
			else $ret .= htmlentities($k) . " ";
		}
		return $ret;
	}
	
	protected function hasChange($diff, $i, $before=0, $after=0) {
		if($before) if(self::hasChange($diff, $i-1, $before -1, 0)) return true;
		if($after) if(self::hasChange($diff, $i+1, 0, $after -1)) return true;
		
		if(!isset($diff[$i])) return false;
		if(!is_array($diff[$i])) return false;
		if($diff[$i]['i'] || $diff[$i]['d']) return true;
		else return false;
	}
	
	function htmlDiffSummary($old, $new){
		$ret = '';
		$diff = self::diff(explode("\n", $old), explode("\n", $new));
		
		$diff_section = false;
		
		foreach($diff as $i=>$k){
			//if we are within 1 lines of a change
			if(self::hasChange($diff,$i,1,1)) {
				//if we aren't already in a diff section, start it
				if(!$diff_section) {
					$diff_section = true;
					$ret .= "<div class='section'><div class='line_number'>Line $i</div>";
				}
			}
			else {
				//close the diff section
				$diff_section = false;
				$ret .= "</div>";
			}
			
			if(is_array($k))
				$ret .= (!empty($k['d'])?"<del>".implode("\n",array_map('htmlentities',$k['d']))."</del>\n":'').
					(!empty($k['i'])?"<ins>".implode("\n",array_map('htmlentities',$k['i']))."</ins>\n":'');
			elseif($diff_section) {
				$ret .= htmlentities($k) . "\n";
			}
		}
		
		if($diff_section) $ret .= "</div>";
		
		return $ret;
	}
}
?>
