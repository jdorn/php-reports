<?php
namespace PhpReports\Filters;

class ClassFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $value->addClass($options['class']);

        return $value;
    }
}
