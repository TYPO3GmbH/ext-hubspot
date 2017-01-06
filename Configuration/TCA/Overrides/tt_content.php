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

$GLOBALS['TCA']['tt_content']['columns']['hubspot_guid'] = [
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
];

$GLOBALS['TCA']['tt_content']['types']['hubspot_form'] = [
    'showitem' => '
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,hubspot_guid,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended,rowDescription,
        --div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category,categories
'
];
