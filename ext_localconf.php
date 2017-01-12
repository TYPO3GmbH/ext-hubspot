<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['hubspot_form'] =
    \T3G\Hubspot\Hooks\PageLayoutView\HubspotPreviewRenderer::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['Hubspot'] = \T3G\Hubspot\Hooks\DataHandler\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'][] = \T3G\Hubspot\Hooks\Typolink\ModifyParameterForPageLinksHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1483626131161] = [
    'nodeName' => 'HubspotCampaign',
    'priority' => 40,
    'class' => \T3G\Hubspot\Form\Element\HubspotCampaignElement::class
];

if (TYPO3_MODE === 'BE') {
    $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $pageRenderer->loadRequireJsModule('TYPO3/CMS/Hubspot/HubspotCtaModule');

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $icons = [
        'ctype-hubspot-form' => 'EXT:hubspot/Resources/Public/Icons/ContentElements/hubspot_form.svg',
    ];
    foreach ($icons as $iconIdentifier => $source) {
        $iconRegistry->registerIcon(
            $iconIdentifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => $source]
        );
    }

}
