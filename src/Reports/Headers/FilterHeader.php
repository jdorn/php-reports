<?php
namespace PhpReports\Headers;

class FilterHeader extends HeaderBase
{
    public static $validation = [
        'column' => [
            'required' => true,
            'type' => 'string',
        ],
        'filter' => [
            'required' => true,
            'type' => 'string',
        ],
        'params' => [
            'type' => 'object',
            'default' => [],
        ],
        'dataset' => [
            'default' => 0,
        ],
    ];

    public static function init($params, &$report)
    {
        $report->addFilter($params['dataset'], $params['column'], $params['filter'], $params['params']);
    }

    //in format: column, params
    //params can be a JSON object or "filter"
    //filter classes are defined in class/filters/
    //examples:
    //	"4,geoip" - apply a geoip filter to the 4th column
    //	'Ip,{"filter":"geoip"}' - apply a geoip filter to the "Ip" column
    public static function parseShortcut($value)
    {
        if (strpos($value, ',') === false) {
            $col = "1";
            $filter = $value;
        } else {
            list($col, $filter) = explode(',', $value, 2);
            $col = trim($col);
        }
        $filter = trim($filter);

        return [
            'column' => $col,
            'filter' => $filter,
            'params' => [],
        ];
    }
}
