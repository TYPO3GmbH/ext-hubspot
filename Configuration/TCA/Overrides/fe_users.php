<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

call_user_func(function (string $extensionName, string $table) {
    $columns = [
        'hubspot_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hubspot_created_timestamp' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hubspot_sync_timestamp' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hubspot_sync_pass' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ]
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $columns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;LLL:EXT:' . $extensionName . '/Resources/Private/Language/locallang_db.xlf:fe_users.tab_label,' . implode(',', array_keys($columns))
    );
}, 'hubspot', 'fe_users');
