<?php

declare(strict_types=1);


namespace T3G\Hubspot\Tests\Unit\Service;


class CustomObjectSynchronizationServiceTest
{
    protected const EXAMPLE_TYPO3_RECORD_DATA = [
        'uid' => 1,
        'tstamp' => 1602791890,
        'first_name' => 'Adam',
        'zip' => '1234',
        'hubspot_id' => 3234574,
        'hubspot_sync_timestamp' => 1602791891,
        'hubspot_sync_pass' => 10,
        'hubspot_created_timestamp' => 1602786169553,
    ];

    protected const EXAMPLE_CUSTOM_OBJECT_DATA = [
        'id' => '123456',
        'properties' => [
            'dealer' => 'UNKNOWN',
            'hs_createdate' => '2022-03-09T15:16:07.517Z',
            'hs_lastmodifieddate' => '2022-03-09T15:16:07.517Z',
            'hs_object_id' => '123456',
            'model' => 'UNKNOWN',
            'purchase_date' => '1970-01-01',
        ],
        'propertiesWithHistory' => [
            'dealer' => [
                '0' => [
                    'value' => 'UNKNOWN',
                    'timestamp' => '2022-03-09T15:16:07.517Z',
                ],

            ],
            'model' => [
                '0' => [
                    'value' => 'UNKNOWN',
                    'timestamp' => '2022-03-09T15:16:07.517Z',
                ],

            ],
            'purchase_date' => [
                '0' => [
                    'value' => '1970-01-01',
                    'timestamp' => '2022-03-09T15:16:07.517Z',
                ],

            ],
        ],
        'createdAt' => '2022-03-09T15:16:07.517Z',
        'updatedAt' => '2022-03-09T15:16:07.517Z',
        'archived' => false
    ];
}
