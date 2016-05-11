<?php
namespace PhpReports\Filters;

class GeoipFilter implements Filter
{
    /**
     * @{inheritDoc}
     */
    public static function filter($value, $options = [], &$report, &$row)
    {
        $record = geoip_record_by_name($value->getValue());

        if ($record) {
            $display = '';

            $display = $record['city'];
            if ($record['country_code'] !== 'US') {
                $display .= ' '.$record['country_name'];
            } else {
                $display .= ', '.$record['region'];
            }

            $value->setValue($display);

            $value->chart_value = ['Latitude' => $record['latitude'], 'Longitude' => $record['longitude'], 'Location' => $display];
        } else {
            $value->chart_value = ['Latitude' => 0, 'Longitude' => 0, 'Location' => 'Unknown'];
        }

        return $value;
    }
}
