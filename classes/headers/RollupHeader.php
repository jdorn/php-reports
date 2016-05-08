<?php

class RollupHeader extends HeaderBase
{
    public static $validation = [
        'columns' => [
            'required' => true,
            'type' => 'object',
            'default' => [],
        ],
        'dataset' => [
            'required' => false,
            'default' => 0,
        ],
    ];

    public static function init($params, &$report)
    {
        //make sure at least 1 column is defined
        if (empty($params['columns'])) {
            throw new Exception("Rollup header needs at least 1 column defined");
        }

        if (!isset($report->options['Rollup'])) {
            $report->options['Rollup'] = [];
        }

        // If more than one dataset is defined, add the rollup header multiple times
        if (is_array($params['dataset'])) {
            $new_params = $params;
            foreach ($params['dataset'] as $dataset) {
                $new_params['dataset'] = $dataset;
                $report->options['Rollup'][] = $new_params;
            }
        } else {
            // Otherwise, just add one rollup header
            $report->options['Rollup'][] = $params;
        }
    }

    public static function beforeRender(&$report)
    {
        //cache for Twig parameters for each dataset/column
        $twig_params = [];

        // Now that we know how many datasets we have, expand out Rollup headers with dataset->true
        $new_rollups = [];
        foreach ($report->options['Rollup'] as $i => $rollup) {
            if ($rollup['dataset'] === true && isset($report->options['DataSets'])) {
                $copy = $rollup;
                foreach ($report->options['DataSets'] as $i => $dataset) {
                    $copy['dataset'] = $i;
                    $new_rollups[] = $copy;
                }
            } else {
                $new_rollups[] = $rollup;
            }
        }
        $report->options['Rollup'] = $new_rollups;

        // First get all the values
        foreach ($report->options['Rollup'] as $rollup) {
            // If we already got twig parameters for this dataset, skip it
            if (isset($twig_params[$rollup['dataset']])) {
                continue;
            }
            $twig_params[$rollup['dataset']] = [];
            if (isset($report->options['DataSets'])) {
                if (isset($report->options['DataSets'][$rollup['dataset']])) {
                    foreach ($report->options['DataSets'][$rollup['dataset']]['rows'] as $row) {
                        foreach ($row['values'] as $value) {
                            if (!isset($twig_params[$rollup['dataset']][$value->key])) {
                                $twig_params[$rollup['dataset']][$value->key] = ['values' => []];
                            }
                            $twig_params[$rollup['dataset']][$value->key]['values'][] = $value->getValue();
                        }
                    }
                }
            }
        }

        // Then, calculate other statistical properties
        foreach ($twig_params as $dataset => &$tp) {
            foreach ($tp as $column => &$params) {
                //get non-null values and sort them
                $real_values = array_filter(
                    $params['values'],
                    function ($a) {
                        if ($a === null || $a === '') {
                            return false;
                        }

                        return true;
                    }
                );

                sort($real_values);

                $params['sum'] = array_sum($real_values);
                $params['count'] = count($real_values);
                if ($params['count']) {
                    $params['mean'] = $params['average'] = $params['sum'] / $params['count'];
                    $params['median'] = ($params['count']%2) ? ($real_values[$params['count']/2-1] + $real_values[$params['count']/2])/2 : $real_values[floor($params['count']/2)];
                    $params['min'] = $real_values[0];
                    $params['max'] = $real_values[$params['count']-1];
                } else {
                    $params['mean'] = $params['average'] = $params['median'] = $params['min'] = $params['max'] = 0;
                }

                $devs = [];
                if (empty($real_values)) {
                    $params['stdev'] = 0;
                } elseif (function_exists('stats_standard_deviation')) {
                    $params['stdev'] = stats_standard_deviation($real_values);
                } else {
                    foreach ($real_values as $v) {
                        $devs[] = pow($v - $params['mean'], 2);
                    }
                    $params['stdev'] = sqrt(array_sum($devs) / (count($devs)));
                }
            }
        }

        //render each rollup row
        foreach ($report->options['Rollup'] as $rollup) {
            if (!isset($report->options['DataSets'][$rollup['dataset']]['footer'])) {
                $report->options['DataSets'][$rollup['dataset']]['footer'] = [];
            }
            $columns = $rollup['columns'];
            $row = [
                'values' => [],
                'rollup' => true,
            ];

            foreach ($twig_params[$rollup['dataset']] as $column => $p) {
                if (isset($columns[$column])) {
                    $p = array_merge($p, ['row' => $twig_params[$rollup['dataset']]]);

                    $row['values'][] = new ReportValue(-1, $column, PhpReports::renderString($columns[$column], $p));
                } else {
                    $row['values'][] = new ReportValue(-1, $column, null);
                }
            }
            $report->options['DataSets'][$rollup['dataset']]['footer'][] = $row;
        }
    }
}
