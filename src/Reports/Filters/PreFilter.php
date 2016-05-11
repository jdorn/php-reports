<?php
namespace PhpReports\Filters;

class PreFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $value->setValue('<pre>'.$value->getValue(true).'</pre>', true);

        return $value;
    }
}
