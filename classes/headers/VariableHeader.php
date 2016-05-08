<?php

class VariableHeader extends HeaderBase
{
    public static $validation = [
        'name' => [
            'required' => true,
            'type' => 'string',
        ],
        'display' => [
            'type' => 'string',
        ],
        'type' => [
            'type' => 'enum',
            'values' => ['text', 'select', 'textarea', 'date', 'daterange'],
            'default' => 'text',
        ],
        'options' => [
            'type' => 'array',
        ],
        'default' => [],
        'empty' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'multiple' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'database_options' => [
            'type' => 'object',
        ],
        'description' => [
            'type' => 'string',
        ],
        'format' => [
            'type' => 'string',
            'default' => 'Y-m-d H:i:s',
        ],
        'modifier_options' => [
            'type' => 'array',
        ],
        'time_offset' => [
            'type' => 'number',
        ],
    ];

    public static function init($params, &$report)
    {
        if (!isset($params['display']) || !$params['display']) {
            $params['display'] = $params['name'];
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/', $params['name'])) {
            throw new Exception("Invalid variable name: $params[name]");
        }

        //add to options
        if (!isset($report->options['Variables'])) {
            $report->options['Variables'] = [];
        }
        $report->options['Variables'][$params['name']] = $params;

        //add to macros
        if (!isset($report->macros[$params['name']]) && isset($params['default'])) {
            $report->addMacro($params['name'], $params['default']);

            $report->macros[$params['name']] = $params['default'];

            if (!isset($params['empty']) || !$params['empty']) {
                $report->is_ready = false;
            }
        } elseif (!isset($report->macros[$params['name']])) {
            $report->addMacro($params['name'], '');

            if (!isset($params['empty']) || !$params['empty']) {
                $report->is_ready = false;
            }
        }

        //convert newline separated strings to array for vars that support multiple values
        if ($params['multiple'] && !is_array($report->macros[$params['name']])) {
            $report->addMacro($params['name'], explode("\n", $report->macros[$params['name']]));
        }

        $report->exportHeader('Variable', $params);
    }

    public static function parseShortcut($value)
    {
        list($var, $params) = explode(',', $value, 2);
        $var = trim($var);
        $params = trim($params);

        $parts = explode(',', $params);
        $params = [
            'name' => $var,
            'display' => trim($parts[0]),
        ];

        unset($parts[0]);

        $extra = implode(',', $parts);

        //just "name, label"
        if (!$extra) {
            return $params;
        }

        //if the 3rd item is "LIST", use multi-select
        if (preg_match('/^\s*LIST\s*\b/', $extra)) {
            $params['multiple'] = true;
            $extraexplode = explode(',', $extra, 2);
            $extra = array_pop($extraexplode);
        }

        //table.column, where clause, ALL
        if (preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,[^,]+,\s*ALL\s*$/', $extra)) {
            list($table_column, $where, $all) = explode(',', $extra, 3);
            list($table, $column) = explode('.', $table_column, 2);

            $params['type'] = 'select';

            $var_params = [
                'table' => $table,
                'column' => $column,
                'all' => true,
                'where' => $where,
            ];

            $params['database_options'] = $var_params;
        } elseif (preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,\s*ALL\s*$/', $extra)) {
            //table.column, ALL
            list($table_column, $all) = explode(',', $extra, 2);
            list($table, $column) = explode('.', $table_column, 2);

            $params['type'] = 'select';

            $var_params = [
                'table' => $table,
                'column' => $column,
                'all' => true,
            ];

            $params['database_options'] = $var_params;
        } elseif (preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*,[^,]+$/', $extra)) {
            //table.column, where clause
            list($table_column, $where) = explode(',', $extra, 2);
            list($table, $column) = explode('.', $table_column, 2);

            $params['type'] = 'select';

            $var_params = [
                'table' => $table,
                'column' => $column,
                'where' => $where,
            ];

            $params['database_options'] = $var_params;
        } elseif (preg_match('/^\s*[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\s*$/', $extra)) {
            //table.column
            list($table, $column) = explode('.', $extra, 2);

            $params['type'] = 'select';

            $var_params = [
                'table' => $table,
                'column' => $column,
            ];

            $params['database_options'] = $var_params;
        } elseif (preg_match('/^\s*([a-zA-Z0-9_\- ]+\|)+[a-zA-Z0-9_\- ]+$/', $extra)) {
            //option1|option2
            $options = explode('|', $extra);

            $params['type'] = 'select';
            $params['options'] = $options;
        }

        return $params;
    }

    public static function afterParse(&$report)
    {
        $classname = $report->options['Type'].'ReportType';

        foreach ($report->options['Variables'] as $var => $params) {
            //if it's a select variable and the options are pulled from a database
            if (isset($params['database_options'])) {
                $classname::openConnection($report);
                $params['options'] = $classname::getVariableOptions($params['database_options'], $report);

                $report->options['Variables'][$var] = $params;
            }

            //if the type is daterange, parse start and end with strtotime
            if ($params['type'] === 'daterange' && !empty($report->macros[$params['name']][0]) && !empty($report->macros[$params['name']][1])) {
                $start = date_create($report->macros[$params['name']][0]);
                if (!$start) {
                    throw new Exception($params['display']." must have a valid start date.");
                }
                date_time_set($start, 0, 0, 0);
                $report->macros[$params['name']]['start'] = date_format($start, $params['format']);

                $end = date_create($report->macros[$params['name']][1]);
                if (!$end) {
                    throw new Exception($params['display']." must have a valid end date.");
                }
                date_time_set($end, 23, 59, 59);
                $report->macros[$params['name']]['end'] = date_format($end, $params['format']);
            }
        }
    }

    public static function beforeRun(&$report)
    {
        foreach ($report->options['Variables'] as $var => $params) {
            //if the type is date, parse with strtotime
            if ($params['type'] === 'date' && $report->macros[$params['name']]) {
                $time = strtotime($report->macros[$params['name']]);
                if (!$time) {
                    throw new Exception($params['display']." must be a valid datetime value.");
                }

                $report->macros[$params['name']] = date($params['format'], $time);
            }
        }
    }
}
