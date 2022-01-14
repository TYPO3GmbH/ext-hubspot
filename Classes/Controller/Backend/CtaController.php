<?php

declare(strict_types=1);


namespace T3G\Hubspot\Controller\Backend;

use T3G\Hubspot\Domain\Repository\Database\ContentElementRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for managing Hubspot CTAs.
 */
class CtaController extends AbstractController
{
    /**
     * Render all used CTAs.
     */
    public function indexAction()
    {
        $contentElementRepository = GeneralUtility::makeInstance(ContentElementRepository::class);
        $contentElements = $contentElementRepository->getContentElementsWithHubspotCta();
        $this->view->assignMultiple([
            'ctasInUse' => $contentElements,
            'returnUrl' => urlencode($this->request->getRequestUri()),
        ]);
    }
}
