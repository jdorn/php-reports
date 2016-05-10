<?php
namespace PhpReports\Filters;

class HideFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        return false;
    }
}
