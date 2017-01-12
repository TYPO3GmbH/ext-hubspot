<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Service\Form;

/**
 * Class ConverterService
 */
class ConverterService
{
    const STRINGIFY = 'stringify-';

    /**
     * @param array $formData
     *
     * @return array
     */
    public function convertToHubspotFormat(array $formData): array
    {
        $hubspotData = [];
        foreach ($formData as $datum) {
            $parts = [];
            if (isset($datum['hubspotTable'], $datum['hubspotProperty'], $datum['value'])) {
                $hubspotProperty = $datum['hubspotProperty'];
                $hubspotTable = $datum['hubspotTable'];

                if (strpos($hubspotProperty, '.') > 0) {
                    $parts = $this->convertToNestedStructure($hubspotProperty, $datum);
                } else {
                    $parts[$hubspotTable][] = [
                        'value' => $datum['value'],
                        'property' => $hubspotProperty,
                    ];
                }
                $hubspotData = array_merge_recursive($hubspotData, $parts);
            }
        }
        $hubspotData = self::normalizeKeys($hubspotData);
        return $hubspotData;
    }

    /**
     * @param $hubspotProperty
     * @param $datum
     * @return array
     */
    protected function convertToNestedStructure($hubspotProperty, $datum): array
    {
        $parts = explode('.', $hubspotProperty);
        array_walk(
            array_reverse($parts), function ($v, $k) use (&$parts, $datum) {
            if (is_numeric($v)) {
                $v = self::STRINGIFY . $v;
            }
            if (!empty($k)) {
                $parts = [$v => $parts];
            } else {
                $parts = [
                    [
                        'property' => $v,
                        'value' => $datum['value'],
                    ],
                ];
            }
        }
        );
        return $parts;
    }

    protected static function normalizeKeys(array $input)
    {
        $return = [];
        $len = strlen(self::STRINGIFY);
        foreach ($input as $key => $value) {
            if (is_string($key) && strpos($key, self::STRINGIFY) === 0) {
                $key = (int)substr($key, $len);
            }

            if (is_array($value)) {
                $value = self::normalizeKeys($value);
            }

            $return[$key] = $value;
        }
        return $return;
    }
}
