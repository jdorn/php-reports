<?php
namespace PhpReports\Filters;

class DrilldownFilter extends LinkFilter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        if (!isset($options['macros'])) {
            $options['macros'] = [];
        }

        //determine report
        //list of reports to try
        $try = [];

        //relative to reportDir
        if ($options['report']{0} === '/') {
            $try[] = substr($options['report'], 1);
        } else {
            //relative to parent report
            $temp = explode('/', $report->report);
            array_pop($temp);
            $try[] = implode('/', $temp).'/'.$options['report'];
            $try[] = $options['report'];
        }

        //see if the file exists directly
        $found = false;
        $path = '';
        foreach ($try as $report_name) {
            if (file_exists(PhpReports::$config['reportDir'].'/'.$report_name)) {
                $path = $report_name;
                $found = true;
                break;
            }
        }

        //see if the report is missing a file extension
        if (!$found) {
            foreach ($try as $report_name) {
                $possible_reports = glob(PhpReports::$config['reportDir'].'/'.$report_name.'.*');

                if ($possible_reports) {
                    $path = substr($possible_reports[0], strlen(PhpReports::$config['reportDir'].'/'));
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            return $value;
        }

        $url = PhpReports::$request->base.'/report/html/?report='.$path;

        $macros = [];
        foreach ($options['macros'] as $k => $v) {
            //if the macro needs to be replaced with the value of another column
            if (isset($v['column'])) {
                if (isset($row[$v['column']])) {
                    $v = $row[$v['column']];
                } else {
                    $v = "";
                }
            } elseif (isset($v['constant'])) {
                //if the macro is just a constant
                $v = $v['constant'];
            }

            $macros[$k] = $v;
        }

        $macros = array_merge($report->macros, $macros);
        unset($macros['host']);

        foreach ($macros as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $v2) {
                    $url .= '&macros['.$k.'][]='.$v2;
                }
            } else {
                $url .= '&macros['.$k.']='.$v;
            }
        }

        $options = [
            'url' => $url,
        ];

        return parent::filter($value, $options, $report, $row);
    }
}
