<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

use Psr\Log\LoggerAwareTrait;
use T3G\Hubspot\Repository\Exception\HubspotExistingContactConflictException;
use T3G\Hubspot\Repository\Exception\UnexpectedMissingContactException;
use T3G\Hubspot\Repository\HubspotContactRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use T3G\Hubspot\Repository\FrontendUserRepository;

/**
 * Service handling contact synchronization between TYPO3 frontend users and Hubspot contacts
 */
class ContactSynchronizationService
{
    use LoggerAwareTrait;

    /**
     * @var HubspotContactRepository
     */
    protected $contactRepository = null;

    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    /**
     * @var array[] UIDs of frontend users processed
     */
    protected $processedRecords = [
        'addedToHubspot' => [], // Frontend user records added to Hubspot
        'addedToFrontendUsers' => [], // New frontend user records added from Hubspot
        'modifiedInHubspot' => [], // Frontend users that had been updated in Hubspot
        'modifiedInFrontendUsers' => [], // Frontend users that had been updated in TYPO3
        'frontendUsersNotSynchronized' => [], // Frontend users processed, but not synchronized
        'frontendUsersWithError' => []
    ];

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
        $this->frontendUserRepository->setLimit($limit);

        $frontendUsers = $this->frontendUserRepository->findReadyForSyncPass();

        foreach ($frontendUsers as $frontendUser) {
            $this->synchronizeFrontendUser($frontendUser);
        }
    }

    public function synchronizeFrontendUser(array $frontendUser)
    {
        if (!GeneralUtility::validEmail($frontendUser['email'])) {
            $this->logger->warning('Frontend user has invalid email and can\'t be synced.', $frontendUser);
            $this->processedRecords['frontendUsersWithError'][] = $frontendUser['uid'];
            return;
        }

        if ($frontendUser['hubspot_id'] === 0) {
            $this->addFrontendUserToHubspot($frontendUser);
        }

        var_dump($this->processedRecords);
    }

    /**
     * Returns information about synchronized records
     *
     * @return array[]
     */
    public function getProcessedRecords(): array
    {
        return $this->processedRecords;
    }

    protected function addFrontendUserToHubspot(array $frontendUser)
    {
        try {
            $hubspotContactIdentifier = $this->contactRepository->createContact(
                $this->mapFrontendUserToHubspotContactProperties($frontendUser)
            );
        } catch (HubspotExistingContactConflictException $existingContactException) {
            $hubspotContact = $this->contactRepository->findByEmail($frontendUser['email']);

            if ($hubspotContact !== null) {
                $frontendUser['hubspot_id'] = $hubspotContact['vid'];

                $this->frontendUserRepository->updateUser(
                    $frontendUser['uid'],
                    ['hubspot_id' => $frontendUser['hubspot_id']]
                );

                $this->synchronizeFrontendUser($frontendUser);

                return;
            }

            throw new UnexpectedMissingContactException(
                'Hubspot says a contact with email "' . $frontendUser['email'] . '" exists, but it can\'t be found.',
                1602244843
            );
        }

        $this->frontendUserRepository->updateUser(
            $frontendUser['uid'],
            ['hubspot_id' => $hubspotContactIdentifier]
        );

        $this->processedRecords['addedToHubspot'][] = $frontendUser['uid'];
    }

    protected function mapFrontendUserToHubspotContactProperties(array $frontendUser)
    {
        $frontendUserToHubSpotContactPropertyMapping = [
            'email' => 'email',
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'company' => 'company',
            'telephone' => 'phone',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'country' => '',
            'www' => 'website',
        ];

        $hubspotContactProperties = [];

        foreach ($frontendUserToHubSpotContactPropertyMapping as $frontendUserMapping => $hubspotMapping) {
            if (isset($frontendUser[$frontendUserMapping]) && $hubspotMapping !== '') {
                $hubspotContactProperties[$hubspotMapping] = $frontendUser[$frontendUserMapping];
            }
        }

        if ($frontendUser['name'] !== '' && $frontendUser['first_name'] === '' && $frontendUser['last_name'] === '') {
            $nameParts = explode(' ', trim($frontendUser['name']));
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            $hubspotContactProperties[$frontendUserToHubSpotContactPropertyMapping['first_name']] = $firstName;
            $hubspotContactProperties[$frontendUserToHubSpotContactPropertyMapping['last_name']] = $lastName;
        }

        return $hubspotContactProperties;
    }
}
