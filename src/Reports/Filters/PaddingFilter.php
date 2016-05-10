<?php
namespace PhpReports\Filters;

class PaddingFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        if ($options['direction'] === 'r') {
            $value->addClass('right');
        } elseif ($options['direction'] === 'l') {
            $value->addClass('left');
        }

        return $value;
    }
}
