<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

use T3G\Hubspot\Repository\HubspotContactRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use T3G\Hubspot\Repository\FrontendUserRepository;

/**
 * Service handling contact synchronization between TYPO3 frontend users and Hubspot contacts
 */
class ContactSynchronizationService
{
    /**
     * @var HubspotContactRepository
     */
    protected $contactRepository = null;

    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    public function __construct()
    {
        $this->contactRepository = GeneralUtility::makeInstance(HubspotContactRepository::class);
        $this->frontendUserRepository = GeneralUtility::makeInstance(FrontendUserRepository::class);
    }

    /**
     * Synchronize TYPO3 frontend users and Hubspot contacts
     *
     * @param int $limit
     */
    public function synchronizeContacts($limit = 10)
    {
        $contacts = $this->contactRepository->getContacts();
        var_dump($contacts['contacts'][0]);
    }
}
