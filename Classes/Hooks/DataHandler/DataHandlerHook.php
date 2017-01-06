<?php
declare(strict_types = 1);

namespace T3G\Hubspot\Hooks\DataHandler;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class DataHandlerHook
{
    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param int|string $id
     * @param DataHandler $handler
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, string $table, $id, DataHandler $handler)
    {
        if ($table === 'pages') {
            if (isset($incomingFieldArray['hubspot_utmcampaign_fulllink'])) {
                $query = parse_url($incomingFieldArray['hubspot_utmcampaign_fulllink'], PHP_URL_QUERY);
                parse_str($query, $result);
                if (isset($result['utm_campaign'])) {
                    $incomingFieldArray['hubspot_utmcampaign'] = $result['utm_campaign'];
                }
                if (isset($result['utm_medium'])) {
                    $incomingFieldArray['hubspot_utmmedium'] = $result['utm_medium'];
                }
                if (isset($result['utm_source'])) {
                    $incomingFieldArray['hubspot_utmsource'] = $result['utm_source'];
                }
            }
        }
    }

}
