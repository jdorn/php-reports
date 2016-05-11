<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

interface FormatInterface
{
    /**
     * @todo DocBlock
     */
    public static function display(Report &$report, Request &$request = null);
}
