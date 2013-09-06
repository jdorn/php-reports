<?php
/** 
 * @version V4.93 10 Oct 2006 (c) 2000-2012 John Lim (jlim#natsoft.com). All rights reserved.
 * Released under both BSD license and Lesser GPL library license. 
 * Whenever there is any discrepancy between the two licenses, 
 * the BSD license will take precedence. 
 *
 * Set tabs to 4 for best viewing.
 * 
*/

/*
 * Concept from daniel.lucazeau@ajornet.com. 
 *
 * @param db		Adodb database connection
 * @param tables	List of tables to join
 * @rowfields		List of fields to display on each row
 * @colfield		Pivot field to slice and display in columns, if we want to calculate ranges, we pass in an array
 * @where			Where clause. Optional.
 * @aggfield		This is the field to sum. Optional. 
 * @sumlabel		Prefix to display in sum columns. Optional.
 * @aggfn			Aggregate function to use (could be AVG, SUM, COUNT)
 * @showcount		Show count of records
 *
 * @returns			Sql generated
 */

function PivotTableSQL(&$db, $tables, $rowfields, $colfield, $where = false, $orderBy = false, $limit = false,
                        $aggfield = false, $sumlabel = "Sum {}", $aggfn = "SUM", $includeaggfield = true, $showcount = true) {
	if ($aggfield) {
        $hidecnt = true;
    } else {
        $hidecnt = false;
    }

    $sumlabel = is_null($sumlabel) ? "Sum {}" : $sumlabel;
    $aggfn = is_null($aggfn) ? "SUM" : $aggfn;
	
	$iif = strpos($db->databaseType,'access') !== false; 
	// note - vfp 6 still doesn' work even with IIF enabled || $db->databaseType == 'vfp';
	
 	if ($where) $where = "\nWHERE $where";
	if (!is_array($colfield)) $colarr = $db->GetCol("select distinct $colfield from $tables $where order by 1");
	$hidecnt = $aggfield ? true : false;
	
	$sel = "$rowfields, ";
	if (is_array($colfield)) {
		foreach ($colfield as $k => $v) {
			$k = trim($k);
			if (!$hidecnt) {
				$sel .= $iif ? 
					"\n\t$aggfn(IIF($v,1,0)) AS \"$k\", "
					:
					"\n\t$aggfn(CASE WHEN $v THEN 1 ELSE 0 END) AS \"$k\", ";
			}
			if ($aggfield) {
				$sel .= $iif ?
					"\n\t$aggfn(IIF($v,$aggfield,0)) AS \"$sumlabel$k\", "
					:
					"\n\t$aggfn(CASE WHEN $v THEN $aggfield ELSE 0 END) AS \"$sumlabel$k\", ";
			}
		} 
	} else {
		foreach ($colarr as $v) {
			if (!is_numeric($v)) $vq = $db->qstr($v);
			else $vq = $v;
			$v = trim($v);
			if (strlen($v) == 0	) $v = 'null';
			if (!$hidecnt) {
				$sel .= $iif ?
					"\n\t$aggfn(IIF($colfield=$vq,1,0)) AS \"$v\", "
					:
					"\n\t$aggfn(CASE WHEN $colfield=$vq THEN 1 ELSE 0 END) AS \"$v\", ";
			}
			if ($aggfield) {
				if ($hidecnt) $label = $v;
				else $label = "{$v}_$aggfield";
				$sel .= $iif ?
					"\n\t$aggfn(IIF($colfield=$vq,$aggfield,0)) AS \"$label\", "
					:
					"\n\t$aggfn(CASE WHEN $colfield=$vq THEN $aggfield ELSE 0 END) AS \"$label\", ";
			}
		}
	}
	if ($includeaggfield && ($aggfield && $aggfield != '1')) {
		$agg = "$aggfn($aggfield)";
        if (strstr($sumlabel, '{}')) {
            $sumlabel = trim($sumlabel, ' \t\n\r\0\x0B{}').' '.trim($aggfield);
        }
        $sel .= "\n\t$agg AS \"$sumlabel\", ";
	}
	
	if ($showcount) {
		$sel .= "\n\tSUM(1) as Total";
    } else {
		$sel = substr($sel,0,strlen($sel)-2);
    }

    if ($orderBy) {
        $orderSql = "\nORDER BY $orderBy";
    }
    if ($limit) {
        if (is_numeric($limit)) {
            $limitSql = "\nLIMIT $limit";
        } else {
            $limitSql = "\n-- LIMIT was declared as non-numeric value\n";
        }
    }

	// Strip aliases
	$rowfields = preg_replace('/\s+AS\s+[\'\"]?[\w\s]+[\'\"]?/i', '', $rowfields);
	
	$sql = "SELECT $sel \nFROM $tables $where \nGROUP BY $rowfields $orderSql $limitSql";
	
	return $sql;
}
