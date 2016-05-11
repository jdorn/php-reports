<?php
namespace PhpReports\Filters;

class TwigFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        // If this is html
        $html = isset($options['html']) ? $options['html'] : false;

        $template = isset($options['template']) ? $options['template'] : $value->getValue();

        $result = PhpReports::renderString($template, [
            'value' => $value->getValue(),
            'row' => $row,
        ]);

        $value->setValue($result, $html);

        return $value;
    }
}
