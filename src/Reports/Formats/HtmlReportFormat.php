<?php
namespace PhpReports\Formats;

use PhpReports\PhpReports;
use PhpReports\Report;
use flight\net\Request;

class HtmlReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        //determine if this is an asyncronous report or not
        $report->async = !isset($request->query['content_only']);
        if (isset($request->query['no_async'])) {
            $report->async = false;
        }

        //if we're only getting the report content
        if (isset($request->query['content_only'])) {
            $template = 'html/content_only';
        } else {
            $template = 'html/report';
        }

        try {
            $additional_vars = [];
            if (isset($request->query['no_charts'])) {
                $additional_vars['no_charts'] = true;
            }

            $html = $report->renderReportPage($template, $additional_vars);
            echo $html;
        } catch (\Exception $e) {
            if (isset($request->query['content_only'])) {
                $template = 'html/blank_page';
            }

            $vars = [
                'title' => $report->report,
                'header' => '<h2>There was an error running your report</h2>',
                'error' => $e->getMessage(),
                'content' => "<h2>Report Query</h2>" . $report->options['Query_Formatted'],
            ];

            echo PhpReports::render($template, $vars);
        }
    }
}
