<?php
namespace PhpReports\Filters;

use Imagick;

class ImgsizeFilter implements Filter
{
    /**
     * @static string
     */
    public static $default_format = '{{ geometry.width }}x{{ geometry.height }} {{ compression }}, {{ fileSize }}';

    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $handle = fopen($value->getValue(), 'rb');
        $img = new Imagick();
        $img->readImageFile($handle);
        $data = $img->identifyImage();

        if (!isset($options['format'])) {
            $options['format'] = self::$default_format;
        }

        $value->setValue(PhpReports::renderString($options['format'], $data));

        return $value;
    }
}
