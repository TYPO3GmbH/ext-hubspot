<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$columns = [
    'hubspot_utmcampaign_fulllink' => [
        'label' => 'LLL:EXT:hubspot/Resources/Private/Language/locallang_db.xlf:pages.utm_campaign_main_label',
        'config' => [
            'type' => 'text',
            'renderType' => 'HubspotCampaign',
            'size' => 300,
            'default' => '',
        ],
    ],
    'hubspot_utmsource' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ],
    'hubspot_utmcampaign' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ],
    'hubspot_utmmedium' => [
        'config' => [
            'type' => 'passthrough',
        ],
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--div--;LLL:EXT:hubspot/Resources/Private/Language/locallang_db.xlf:pages.tab_label,' . implode(',', array_keys($columns)));
