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

            $backendController = \T3G\Hubspot\Controller\BackendController::class;
            $extensionName = 'hubspot';
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class)
                    ->getMajorVersion() < 10) {
                $backendController = 'Backend';
                $extensionName = 'T3G.hubspot';
            }


            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
                $extensionName,
                'hubspotForm',
                'Hubspot Form'
            );
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                $extensionName,
                'tools',
                'tx_Hubspot',
                'top',
                [
                    $backendController => 'index, forms, hubspotForm, ctas'
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
