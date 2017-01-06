<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Service\Form;


class ConverterService
{

    public function convertToHubspotFormat(array $formData)
    {
        $hubspotData = [];
        foreach ($formData as $datum) {
            if (isset($datum['hubspotTable']) && isset($datum['hubspotProperty']) && isset($datum['value'])) {
                $hubspotProperty = $datum['hubspotProperty'];
                $hubspotTable = $datum['hubspotTable'];

                if (strpos($hubspotProperty, '.') > 0) {
                    list($key, $property) = explode('.', $hubspotProperty);
                } else {
                    $key = 0;
                    $property = $hubspotProperty;
                }
                $hubspotData[$hubspotTable][$key][] = [
                    'property' => $property,
                    'value' => $datum['value']
                ];
            }
        }

        return $hubspotData;
    }
}