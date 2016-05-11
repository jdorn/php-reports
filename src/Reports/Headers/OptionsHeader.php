<?php
namespace PhpReports\Headers;

class OptionsHeader extends HeaderBase
{
    /**
     * @var array
     */
    public static $validation = [
        'limit' => [
            'type' => 'number',
            'default' => null,
        ],
        'access' => [
            'type' => 'enum',
            'values' => ['rw', 'readonly'],
            'default' => 'readonly',
        ],
        'noborder' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'noreport' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'vertical' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'ignore' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'table' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'showcount' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'font' => [
            'type' => 'string',
        ],
        'stop' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'nodata' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'version' => [
            'type' => 'number',
            'default' => 1,
        ],
        'selectable' => [
            'type' => 'string',
        ],
        'mongodatabase' => [
            'type' => 'string',
        ],
        'database' => [
            'type' => 'string',
        ],
        'cache' => [
            'min' => 0,
            'type' => 'number',
        ],
        'ttl' => [
            'min' => 0,
            'type' => 'number',
        ],
        'default_dataset' => [
            'type' => 'number',
            'default' => 0,
        ],
        'has_charts' => [
            'type' => 'boolean',
        ],
    ];

    /**
     * @{inheritDoc}
     */
    public static function init($params, &$report)
    {
        //legacy support for the 'ttl' cache parameter
        if (isset($params['ttl'])) {
            $params['cache'] = $params['ttl'];
            unset($params['ttl']);
        }

        if (isset($params['has_charts']) && $params['has_charts']) {
            if (!isset($report->options['Charts'])) {
                $report->options['Charts'] = [];
            }
        }

        // Some parameters were moved to a 'FORMATTING' header
        // We need to catch those and add the header to the report
        $formatting_header = [];

        foreach ($params as $key => $value) {
            // This is a FORMATTING parameter
            if (in_array($key, ['limit', 'noborder', 'vertical', 'table', 'showcount', 'font', 'nodata', 'selectable'])) {
                $formatting_header[$key] = $value;
                continue;
            }

            //some of the keys need to be uppercase (for legacy reasons)
            if (in_array($key, ['database', 'mongodatabase', 'cache'])) {
                $key = ucfirst($key);
            }

            $report->options[$key] = $value;

            //if the value is different from the default, it can be exported
            if (!isset(self::$validation[$key]['default']) || ($value && $value !== self::$validation[$key]['default'])) {
                //only export some of the options
                if (in_array($key, array('access', 'Cache'), true)) {
                    $report->exportHeader('Options', array($key => $value));
                }
            }
        }

        if ($formatting_header) {
            $formatting_header['dataset'] = true;
            $report->parseHeader('Formatting', $formatting_header);
        }
    }

    public static function parseShortcut($value)
    {
        $options = explode(',', $value);

        $params = [];

        foreach ($options as $v) {
            if (strpos($v, '=') !== false) {
                list($k, $v) = explode('=', $v, 2);
                $v = trim($v);
            } else {
                $k = $v;
                $v = true;
            }

            $k = trim($k);

            $params[$k] = $v;
        }

        return $params;
    }
}
