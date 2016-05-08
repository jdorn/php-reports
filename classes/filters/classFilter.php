<?php

class classFilter extends FilterBase
{
    public static function filter($value, $options = [], &$report, &$row)
    {
        $value->addClass($options['class']);

        return $value;
    }
}
