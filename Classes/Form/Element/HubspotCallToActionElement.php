<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Form\Element;

use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class HubspotCallToActionElement extends TextElement {

    public function render()
    {
        $resultArray = parent::render();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Hubspot/HubspotCtaModule');
        $descriptionHtml = $this->getLanguageService()->sL('LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:tx_hubspot_cta.hubspot_cta_code.description');
        $resultArray['html'] .= '<p class="help-block">' . $descriptionHtml . '</p>';
        return $resultArray;

    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}