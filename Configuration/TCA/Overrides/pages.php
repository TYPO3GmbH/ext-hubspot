<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'hubspot',
    'Configuration/PageTS/MOD.tsconfig',
    'Hubspot Integration'
);

$columns = [
    'hubspot_utmcampaign_fulllink' => [
        'label' => 'LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:pages.utm_campaign_main_label',
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--div--;LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:pages.tab_label,' . implode(',', array_keys($columns)));
