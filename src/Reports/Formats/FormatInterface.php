<?php
namespace PhpReports\Formats;

interface FormatInterface
{
    /**
     * @todo DocBlock
     */
    public static function display(&$report, &$request);
}
