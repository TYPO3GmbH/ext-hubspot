<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3_MODE') || die();

call_user_func(function (string $extensionName) {
    if (!\T3G\Hubspot\Utility\CompatibilityUtility::isComposerMode()) {
        @include 'phar://' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('hubspot') . 'Libraries/hubspot-php.phar/vendor/autoload.php';
    }
    /***************
     * PageTS
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extensionName . '/Configuration/TsConfig/Page/All.tsconfig">'
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['hubspot_form'] =
        \T3G\Hubspot\Hooks\PageLayoutView\HubspotPreviewRenderer::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['Hubspot'] = \T3G\Hubspot\Hooks\DataHandler\DataHandlerHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'][] = \T3G\Hubspot\Hooks\Typolink\ModifyParameterForPageLinksHook::class;

    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1483626131161] = [
            'nodeName' => 'HubspotCampaign',
            'priority' => 40,
            'class' => \T3G\Hubspot\Form\Element\HubspotCampaignElement::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1484299681] = [
            'nodeName' => 'HubspotCallToAction',
            'priority' => 40,
            'class' => \T3G\Hubspot\Form\Element\HubspotCallToActionElement::class,
        ];

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $icons = [
            'ctype-hubspot-form' => 'EXT:' . $extensionName . '/Resources/Public/Icons/ContentElements/hubspot_form.svg',
            'ctype-hubspot-cta' => 'EXT:' . $extensionName . '/Resources/Public/Icons/ContentElements/hubspot_cta.svg',
            'hubspot-custom-object' => 'EXT:' . $extensionName . '/Resources/Public/Icons/hubspot_custom_object.svg',
        ];
        foreach ($icons as $iconIdentifier => $source) {
            $iconRegistry->registerIcon(
                $iconIdentifier,
                \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                ['source' => $source]
            );
        }

        if (\T3G\Hubspot\Utility\CompatibilityUtility::typo3VersionIsLessThan('9.3')) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['be'][] = 'T3G\\Hubspot\\ViewHelpers\\Compatibility\\Backend';
        }
    }
}, 'hubspot');
