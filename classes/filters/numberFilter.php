<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lrzanek
 * Date: 30.08.13
 * Time: 15:37
 * To change this template use File | Settings | File Templates.
 */
class numberFilter extends FilterBase {
    public static function filter($value, $options = array(), &$report, &$row) {
        $decimals = $options['decimals'] ? $options['decimals'] : 0;
        $dec_sepr = $options['decimal_sep'] ? $options['decimal_sep'] : ',';
        $thousand = $options['thousands_sep'] ? $options['thousands_sep'] : ' ';
        if (is_numeric($value->getValue())) {
            $value->setValue(number_format($value->getValue(), $decimals, $dec_sepr, $thousand), true);
        }

        return $value;
    }
}
