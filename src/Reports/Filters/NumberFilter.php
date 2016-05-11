<?php
namespace PhpReports\Filters;

class NumberFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $decimals = $options['decimals'] ? $options['decimals'] : 0;
        $dec_sepr = $options['decimal_sep'] ? $options['decimal_sep'] : ',';
        $thousand = $options['thousands_sep'] ? $options['thousands_sep'] : ' ';

        if (is_numeric($value->getValue())) {
            $value->setValue(number_format($value->getValue(), $decimals, $dec_sepr, $thousand), true);
        }

        return $value;
    }
}
