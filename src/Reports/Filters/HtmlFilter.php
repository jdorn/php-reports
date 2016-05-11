<?php
namespace PhpReports\Filters;

class HtmlFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $value->is_html = true;

        return $value;
    }
}
