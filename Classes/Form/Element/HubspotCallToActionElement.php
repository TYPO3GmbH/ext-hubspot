<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Form\Element;

use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HubspotCallToActionElement extends TextElement
{
    public function render()
    {
        $resultArray = parent::render();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Hubspot/HubspotCtaModule');
        $descriptionHtml = $this->getLanguageService()->sL('LLL:EXT:hubspot/Resources/Private/Language/locallang_db.xlf:tx_hubspot_cta.hubspot_cta_code.description');
        $resultArray['html'] .= '<p class="help-block">' . $descriptionHtml . '</p>';
        return $resultArray;
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
