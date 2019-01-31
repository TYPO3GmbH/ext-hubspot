<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:plugin.hubspot_form',
        'hubspot_form',
        'EXT:hubspot/Resources/Public/Icons/ContentElements/hubspot_form.svg'
    ],
    'CType',
    'hubspot'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:tt_content.hubspot_cta',
        'hubspot_cta',
        'EXT:hubspot/Resources/Public/Icons/ContentElements/hubspot_cta.svg'
    ],
    'CType',
    'hubspot'
);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['hubspot_cta'] = 'ctype-hubspot-cta';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['hubspot_form'] = 'ctype-hubspot-form';

$columns = [
    'hubspot_guid' => [
        'label' => 'LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:tt_content.hubspot_form',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    ''
                ]
            ],
            'itemsProcFunc' => \T3G\Hubspot\Repository\HubspotFormRepository::class . '->getFormsForItemsProcFunc',
            'default' => '',
        ]
    ],
    'hubspot_cta' => [
        'label' => 'LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:tt_content.hubspot_cta',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_hubspot_cta',
            'size' => '1',
            'maxitems' => '1',
            'minitems' => '0',
        ]
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $columns);

$GLOBALS['TCA']['tt_content']['types']['hubspot_form'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
            hubspot_guid,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    '
];

$GLOBALS['TCA']['tt_content']['types']['hubspot_cta'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
            hubspot_cta,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    '
];
