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
    protected $hubspotContactRepository = null;

    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    const FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING = [
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
        $this->hubspotContactRepository = GeneralUtility::makeInstance(HubspotContactRepository::class);
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
        $this->hubspotContactRepository->setLimit($limit);

        $lastSynchronizationTimestamp = $this->frontendUserRepository->getLatestSynchronizationTimestamp();
        $newHubspotContacts = $this->hubspotContactRepository->findNewSince($lastSynchronizationTimestamp);

        if (count($newHubspotContacts) > 0) {
            foreach ($newHubspotContacts as $newHubspotContact) {
                
            }

            return; // Do more on next run
        }

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
            return;
        }

        $this->compareAndUpdateFrontendUserAndHubspotContact($frontendUser);


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

    protected function addHubspotContactToFrontendUsers()
    {
        
    }

    protected function addFrontendUserToHubspot(array $frontendUser)
    {
        try {
            $hubspotContactIdentifier = $this->hubspotContactRepository->create(
                $this->mapFrontendUserToHubspotContactProperties($frontendUser)
            );
        } catch (HubspotExistingContactConflictException $existingContactException) {
            $hubspotContact = $this->hubspotContactRepository->findByEmail($frontendUser['email']);

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

    protected function compareAndUpdateFrontendUserAndHubspotContact(array $frontendUser)
    {
        if ($frontendUser['hubspot_id'] === 0) {
            $this->addFrontendUserToHubspot($frontendUser);
        }

        $hubspotContact = $this->hubspotContactRepository->findByIdentifier($frontendUser['hubspot_id']);

        if ($hubspotContact === null) {
            return; // We expect the user has been deleted from Hubspot
        }

        $frontendUserChanges = [];
        $hubspotContactChanges = [];

        foreach (static::FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING as $frontendUserMapping => $hubspotMapping) {
            if (!isset($hubspotContact['properties'][$hubspotMapping])) {
                continue; // There's no mapping to this on the hubspot side
            }

            $frontendUserValue = $frontendUser[$frontendUserMapping];
            $hubspotContactProperty = $hubspotContact['properties'][$hubspotMapping];

            if (trim($frontendUserValue) === trim($hubspotContactProperty['value'])) {
                continue; // No change in value
            }

            if ($this->getLatestTimestampFromHubspotProperty($hubspotContactProperty) > $frontendUser['tstamp']) {
                $frontendUserChanges[$frontendUserMapping] = $hubspotContactProperty['value'];
                continue;
            }

            $hubspotContactChanges[$hubspotMapping] = $frontendUserValue;
        }

        if (count($frontendUserChanges) > 0) {
            $this->frontendUserRepository->updateUser($frontendUser['uid'], $frontendUserChanges);
            $this->processedRecords['modifiedInHubspot'][] = $frontendUser['uid'];
        } else {
            $this->frontendUserRepository->setSyncPassSilently($frontendUser['uid']);
            $this->processedRecords['frontendUsersNotSynchronized'][] = $frontendUser['uid'];
        }

        if (count($hubspotContactChanges) > 0) {
            $this->hubspotContactRepository->update($frontendUser['hubspot_id'], $hubspotContactChanges);
            $this->processedRecords['modifiedInFrontendUsers'][] = $frontendUser['uid'];
        }
    }

    protected function mapFrontendUserToHubspotContactProperties(array $frontendUser)
    {
        $hubspotContactProperties = [];

        foreach (static::FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING as $frontendUserMapping => $hubspotMapping) {
            if (isset($frontendUser[$frontendUserMapping]) && $hubspotMapping !== '') {
                $hubspotContactProperties[$hubspotMapping] = $frontendUser[$frontendUserMapping];
            }
        }

        if ($frontendUser['name'] !== '' && $frontendUser['first_name'] === '' && $frontendUser['last_name'] === '') {
            $nameParts = explode(' ', trim($frontendUser['name']));
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            $hubspotContactProperties[static::FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING['first_name']] = $firstName;
            $hubspotContactProperties[static::FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING['last_name']] = $lastName;
        }

        return $hubspotContactProperties;
    }

    protected function getLatestTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        return $hubspotProperty['versions'][0]['timestamp'];
    }
}
