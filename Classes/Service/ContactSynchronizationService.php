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
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;

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

    /**
     * @var array Default values for TypoScript configuration
     */
    protected $defaultConfiguration = [];

    /**
     * @var array Configuration array
     */
    protected $configuration = [];

    /**
     * @var int To track active configuration PID
     */
    protected $activeConfigurationPageId = 0;

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
     * @param int $defaultPid
     */
    public function synchronizeContacts(int $defaultPid = null)
    {
        $this->configureForPageId(
            $defaultPid ?? (int)$this->defaultConfiguration['persistence.']['synchronize.']['defaultPid']
        );

        $latestTimestamp = $this->frontendUserRepository->getLatestSynchronizationTimestamp();

        // If we haven't synced yet, we might still have created records
        if ($latestTimestamp === 0) {
            $latestTimestamp = $this->frontendUserRepository->getLatestHubspotCreatedTimestamp();
        }

        $newHubspotContacts = $this->hubspotContactRepository->findNewSince($latestTimestamp);

        if (count($newHubspotContacts) > 0) {
            foreach ($newHubspotContacts as $newHubspotContact) {
                $this->addHubspotContactToFrontendUsers($newHubspotContact);
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

    /**
     * Set default TypoScript values
     *
     * @param array $defaultConfiguration
     */
    public function setDefaultConfiguration(array $defaultConfiguration)
    {
        $this->defaultConfiguration = $defaultConfiguration;
    }

    /**
     * Fetches TypoScript configuration from page and sets $this->configuration to what's in module.tx_hubspot
     *
     * Also configures repository defaults
     *
     * @param int $pageId
     */
    protected function configureForPageId(int $pageId)
    {
        if ($this->activeConfigurationPageId === $pageId) {
            return;
        }

        $configuration = GeneralUtility::makeInstance(BackendConfigurationManager::class)
                ->getTypoScriptSetup()['module.']['tx_hubspot.'] ?? [];

        $this->configuration = array_merge_recursive($this->defaultConfiguration, $configuration);

        $this->activeConfigurationPageId = $pageId;

        $this->configureRepositoryDefaults();
    }

    /**
     * Sets repository default values based on $this->configuration
     */
    protected function configureRepositoryDefaults()
    {
        $this->frontendUserRepository->setDefaultPageId((int)$this->configuration['persistence.']['synchronize.']['storagePid']);

        if ($this->configuration['synchronize.']['limit']) {
            $this->frontendUserRepository->setLimit((int)$this->configuration['synchronize.']['limit']);
            $this->hubspotContactRepository->setLimit((int)$this->configuration['synchronize.']['limit']);
        }
    }

    protected function addHubspotContactToFrontendUsers(array $hubspotContact)
    {
        $frontendUserIdentifier = $this->frontendUserRepository->create(
            $this->mapHubspotContactToFrontendUserProperties($hubspotContact)
        );

        $this->processedRecords['addedToFrontendUsers'][] = $hubspotContact['vid'];
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

                $this->frontendUserRepository->update(
                    $frontendUser['uid'],
                    [
                        'hubspot_id' => $frontendUser['hubspot_id'],
                        'hubspot_created_timestamp' => time(),
                    ]
                );

                $this->synchronizeFrontendUser($frontendUser);

                return;
            }

            throw new UnexpectedMissingContactException(
                'Hubspot says a contact with email "' . $frontendUser['email'] . '" exists, but it can\'t be found.',
                1602244843
            );
        }

        $this->frontendUserRepository->update(
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
            $this->frontendUserRepository->update($frontendUser['uid'], $frontendUserChanges);
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

    protected function mapFrontendUserToHubspotContactProperties(array $frontendUser): array
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

    protected function mapHubspotContactToFrontendUserProperties(array $hubspotContact): array
    {
        $hubspotContactProperties = $hubspotContact['properties'];

        // Make email into a property
        if (!isset($hubspotContactProperties['email'])) {
            foreach ($hubspotContact['identity-profiles'][0]['identities'] as $identity) {
                if ($identity['type'] === 'EMAIL' && $identity['is-primary']) {
                    $hubspotContactProperties['email'] = $identity['value'];
                }
            }
        }

        $frontendUserProperties = [];

        $hubspotContactToFrontendUserProperty = array_flip(static::FRONTEND_USER_TO_HUBSPOT_CONTACT_PROPERTY_MAPPING);

        foreach ($hubspotContactToFrontendUserProperty as $hubspotMapping => $frontendUserMapping) {
            if (isset($hubspotContactProperties[$hubspotMapping]['value']) && $hubspotMapping !== '') {
                $frontendUserProperties[$frontendUserMapping] = $hubspotContactProperties[$hubspotMapping]['value'];
            }
        }

        $frontendUserProperties['hubspot_created_timestamp'] = $hubspotContact['addedAt'];

        return $frontendUserProperties;
    }

    protected function getLatestTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        return $hubspotProperty['versions'][0]['timestamp'];
    }
}
