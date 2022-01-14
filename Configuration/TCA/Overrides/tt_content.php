<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

call_user_func(function (string $extensionName, string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        $table,
        'CType',
        [
            'LLL:EXT:' . $extensionName . '/Resources/Private/Language/locallang_db.xlf:tt_content.hubspot_form',
            'hubspot_form',
            'EXT:' . $extensionName . '/Resources/Public/Icons/ContentElements/hubspot_form.svg',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        $table,
        'CType',
        [
            'LLL:EXT:' . $extensionName . '/Resources/Private/Language/locallang_db.xlf:tt_content.hubspot_cta',
            'hubspot_cta',
            'EXT:' . $extensionName . '/Resources/Public/Icons/ContentElements/hubspot_cta.svg',
        ]
    );

    $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['hubspot_cta'] = 'ctype-hubspot-cta';
    $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['hubspot_form'] = 'ctype-hubspot-form';

    $columns = [
        'hubspot_guid' => [
            'label' => 'LLL:EXT:hubspot/Resources/Private/Language/locallang_db.xlf:tt_content.hubspot_form',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                    ],
                ],
                'itemsProcFunc' => \T3G\Hubspot\Domain\Repository\Hubspot\HubspotFormRepository::class . '->getFormsForItemsProcFunc',
                'default' => '',
            ],
        ],
        'hubspot_cta' => [
            'label' => 'LLL:EXT:hubspot/Resources/Private/Language/locallang_db.xlf:tt_content.hubspot_cta',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_hubspot_cta',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0',
                'default' => '0',
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $columns);

    $GLOBALS['TCA'][$table]['types']['hubspot_form'] = [
        'showitem' =>
            '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,'
            . '    hubspot_guid,'
            . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,'
            . '    --palette--;;language,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,'
            . '    --palette--;;hidden,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,'
            . '    categories,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,'
            . '    rowDescription,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
    ];

    $GLOBALS['TCA'][$table]['types']['hubspot_cta'] = [
        'showitem' =>
            '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,'
            . '    hubspot_cta,'
            . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,'
            . '    --palette--;;language,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,'
            . '    --palette--;;hidden,'
            . '    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,'
            . '    categories,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,'
            . '    rowDescription,'
            . '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
    ];
}, 'hubspot', 'tt_content');
