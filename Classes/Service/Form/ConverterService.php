<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Service\Form;

/**
 * Class ConverterService
 */
class ConverterService
{

    /**
     * @param array $formData
     *
     * @return array
     */
    public function convertToHubspotFormat(array $formData)
    {
        $hubspotData = [];
        foreach ($formData as $datum) {
            if (isset($datum['hubspotTable'], $datum['hubspotProperty'], $datum['value'])) {
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
