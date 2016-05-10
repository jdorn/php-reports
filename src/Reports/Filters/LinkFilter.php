<?php
namespace PhpReports\Filters;

class LinkFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        if (!$value->getValue()) {
            return $value;
        }

        $url = isset($options['url']) ? $options['url'] : $value->getValue();
        $attr = (isset($options['blank']) && $options['blank']) ? ' target="_blank"' : '';
        $display = isset($options['display']) ? $options['display'] : $value->getValue();

        $value->setValue(
            join('', [
                '<a href="',
                $url,
                '"',
                $attr,
                '>',
                $display,
                '</a>',
            ]),
            true
        );

        return $value;
    }
}
