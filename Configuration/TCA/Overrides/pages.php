<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

call_user_func(function(string $extensionName, string $table) {
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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $columns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;LLL:EXT:' . $extensionName . '/Resources/Private/Language/locallang_db.xlf:pages.tab_label,' . implode(',', array_keys($columns))
    );

}, 'hubspot', 'pages');
