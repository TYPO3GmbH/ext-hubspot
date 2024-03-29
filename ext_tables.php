<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
                'T3G.hubspot',
                'hubspotForm',
                'Hubspot Form'
            );

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'T3G.Hubspot',
                'tools',
                'hubspot',
                'top',
                [
                    \T3G\Hubspot\Controller\Backend\OverviewController::class => 'index',
                    \T3G\Hubspot\Controller\Backend\FormController::class => 'index, editInline',
                    \T3G\Hubspot\Controller\Backend\CtaController::class => 'index',
                    \T3G\Hubspot\Controller\Backend\SchemaController::class => 'index, inspect, refresh, new, create, download, delete'
                ],
                [
                    'access' => 'admin',
                    'icon' => 'EXT:hubspot/Resources/Public/Icons/module-hubspot.svg',
                    'labels' => 'LLL:EXT:hubspot/Resources/Private/Language/locallang_mod.xlf'
                ]
            );
        }
    }
);
