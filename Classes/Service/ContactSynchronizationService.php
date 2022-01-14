<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Configuration\BackendConfigurationManager;
use T3G\Hubspot\Domain\Repository\Database\Exception\DataHandlerErrorException;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\ExistingContactConflictException;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\UnexpectedMissingContactException;
use T3G\Hubspot\Domain\Repository\Database\FrontendUserRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\HubspotContactRepository;
use T3G\Hubspot\Service\Event\AfterAddingFrontendUserToHubspotEvent;
use T3G\Hubspot\Service\Event\AfterAddingHubspotContactToFrontendUsersEvent;
use T3G\Hubspot\Service\Event\AfterContactSynchronizationEvent;
use T3G\Hubspot\Service\Event\AfterMappingFrontendUserToHubspotContactPropertiesEvent;
use T3G\Hubspot\Service\Event\AfterMappingHubspotContactToFrontendUserEvent;
use T3G\Hubspot\Service\Event\AfterUpdatingFrontendUserAndHubspotContactEvent;
use T3G\Hubspot\Service\Event\BeforeAddingFrontendUserToHubspotEvent;
use T3G\Hubspot\Service\Event\BeforeAddingHubspotContactToFrontendUsersEvent;
use T3G\Hubspot\Service\Event\BeforeComparingFrontendUserAndHubspotContactEvent;
use T3G\Hubspot\Service\Event\BeforeContactSynchronizationEvent;
use T3G\Hubspot\Service\Event\BeforeFrontendUserSynchronizationEvent;
use T3G\Hubspot\Service\Event\BeforeMappingFrontendUserToHubspotContactEvent;
use T3G\Hubspot\Service\Event\BeforeMappingHubspotContactToFrontendUserEvent;
use T3G\Hubspot\Service\Event\BeforeUpdatingFrontendUserAndHubspotContactEvent;
use T3G\Hubspot\Service\Event\ResolveHubspotContactEvent;
use T3G\Hubspot\Service\Exception\SkipRecordSynchronizationException;
use T3G\Hubspot\Service\Exception\StopRecordSynchronizationException;
use T3G\Hubspot\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Service handling contact synchronization between TYPO3 frontend users and Hubspot contacts
 */
class ContactSynchronizationService implements LoggerAwareInterface
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

    /**
     * ContactSynchronizationService constructor.
     */
    public function __construct(
        HubspotContactRepository $hubspotContactRepository = null,
        FrontendUserRepository $frontendUserRepository = null
    ) {
        $this->hubspotContactRepository =
            $hubspotContactRepository ?? GeneralUtility::makeInstance(HubspotContactRepository::class);
        $this->frontendUserRepository =
            $frontendUserRepository ?? GeneralUtility::makeInstance(FrontendUserRepository::class);
    }

    /**
     * Synchronize TYPO3 frontend users and Hubspot contacts
     *
     * @param int $defaultPid
     */
    public function synchronize(int $defaultPid = null)
    {
        $this->configureForPageId(
            $defaultPid ?? (int)$this->defaultConfiguration['persistence.']['synchronize.']['defaultPid']
        );

        $beforeSynchronizationEvent = new BeforeContactSynchronizationEvent(
            $this,
            $this->configuration
        );

        try {
            CompatibilityUtility::dispatchEvent($beforeSynchronizationEvent);
        } catch (StopRecordSynchronizationException $e) {
            return;
        }

        $this->configuration = $beforeSynchronizationEvent->getConfiguration();
        unset($beforeSynchronizationEvent);

        if ($this->configuration['settings.']['synchronize.']['createNewInTypo3']) {
            $newHubspotContacts = $this->hubspotContactRepository->findNewBefore(
                $this->frontendUserRepository->getOldestHubspotCreatedTimestamp()
            );

            if (count($newHubspotContacts) > 0) {
                foreach ($newHubspotContacts as $newHubspotContact) {
                    try {
                        $this->addHubspotContactToFrontendUsers($newHubspotContact);
                    } catch (SkipRecordSynchronizationException $e) {
                        continue;
                    } catch (StopRecordSynchronizationException $e) {
                        return;
                    }
                }

                return; // Do more on next run
            }
        }

        $frontendUsers = $this->frontendUserRepository->findNotYetSynchronized();

        if (count($frontendUsers) > 0) {
            $this->frontendUserRepository->fixSyncPassIdentifierScope();

            foreach ($frontendUsers as $frontendUser) {
                try {
                    $this->synchronizeFrontendUser($frontendUser);
                } catch (SkipRecordSynchronizationException $e) {
                    continue;
                } catch (StopRecordSynchronizationException $e) {
                    return;
                }
            }

            return; // Do more on next run
        }

        $frontendUsers = $this->frontendUserRepository->findReadyForSyncPass();

        foreach ($frontendUsers as $frontendUser) {
            try {
                $this->synchronizeFrontendUser($frontendUser);
            } catch (SkipRecordSynchronizationException $e) {
                continue;
            } catch (StopRecordSynchronizationException $e) {
                return;
            }
        }

        $afterSynchronizationEvent = new AfterContactSynchronizationEvent(
            $this,
            $this->configuration,
            $this->processedRecords
        );

        try {
            CompatibilityUtility::dispatchEvent($afterSynchronizationEvent);
        } catch (StopRecordSynchronizationException $e) {
            return;
        }

        $this->configuration = $afterSynchronizationEvent->getConfiguration();
        $this->processedRecords = $afterSynchronizationEvent->getProcessedRecords();
    }

    /**
     * Update a frontend user with hubspot contact or create a new hubspot contact based on the frontend user
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws BadRequest
     * @throws DataHandlerErrorException
     * @internal
     */
    public function synchronizeFrontendUser(array $frontendUser)
    {
        $beforeFrontendUserSynchronizationEvent = new BeforeFrontendUserSynchronizationEvent(
            $this,
            $this->configuration,
            $frontendUser
        );

        CompatibilityUtility::dispatchEvent($beforeFrontendUserSynchronizationEvent);

        $this->configuration = $beforeFrontendUserSynchronizationEvent->getConfiguration();
        $frontendUser = $beforeFrontendUserSynchronizationEvent->getFrontendUser();

        if (!GeneralUtility::validEmail($frontendUser['email'])) {
            $this->logger->warning('Frontend user has invalid email and can\'t be synced.', $frontendUser);
            $this->processedRecords['frontendUsersWithError'][] = $frontendUser['uid'];
            return;
        }

        $this->configureForPageId($frontendUser['pid']);

        if ($frontendUser['hubspot_id'] === 0) {
            $this->addFrontendUserToHubspot($frontendUser);
            return;
        }

        $this->compareAndUpdateFrontendUserAndHubspotContact($frontendUser);
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
     * Sets repository default values based on $this->configuration
     */
    protected function configureRepositoryDefaults()
    {
        $this->frontendUserRepository->setDefaultPageId((int)$this->configuration['persistence.']['synchronize.']['storagePid']);

        if ($this->configuration['synchronize.']['limit']) {
            $this->frontendUserRepository->setLimit((int)$this->configuration['synchronize.']['limit']);
            $this->hubspotContactRepository->setLimit((int)$this->configuration['synchronize.']['limit']);
        }

        $this->frontendUserRepository->setSearchPids(
            GeneralUtility::intExplode(',', $this->configuration['synchronize.']['limitToPids'] ?? '', true)
        );
    }

    /**
     * Use a hubspot contact to create a new frontend user
     *
     * @param array $hubspotContact
     * @throws DataHandlerErrorException
     */
    protected function addHubspotContactToFrontendUsers(array $hubspotContact)
    {
        $beforeAddingEvent = new BeforeAddingHubspotContactToFrontendUsersEvent(
            $this,
            $this->configuration,
            $hubspotContact
        );

        CompatibilityUtility::dispatchEvent($beforeAddingEvent);

        $this->configuration = $beforeAddingEvent->getConfiguration();
        $hubspotContact = $beforeAddingEvent->getHubspotContact();
        unset($beforeAddingEvent);

        $mappedFrontendUserProperties = $this->mapHubspotContactToFrontendUserProperties($hubspotContact);

        $ignoreOnFrontendUserUpdate = GeneralUtility::trimExplode(
            ',',
            $this->configuration['settings.']['synchronize.']['ignoreOnFrontendUserCreate'],
            true
        );
        foreach ($ignoreOnFrontendUserUpdate as $propertyName) {
            unset($mappedFrontendUserProperties[$propertyName]);
        }

        $frontendUserIdentifier = $this->frontendUserRepository->create($mappedFrontendUserProperties);

        $this->processedRecords['addedToFrontendUsers'][] = $hubspotContact['vid'];

        $afterAddingEvent = new AfterAddingHubspotContactToFrontendUsersEvent(
            $this,
            $this->configuration,
            $hubspotContact,
            $frontendUserIdentifier
        );

        CompatibilityUtility::dispatchEvent($afterAddingEvent);

        $this->configuration = $afterAddingEvent->getConfiguration();
    }

    /**
     * Use a frontend user to create a hubspot user
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws BadRequest
     * @throws DataHandlerErrorException
     */
    public function addFrontendUserToHubspot(array $frontendUser)
    {
        $beforeAddEvent = new BeforeAddingFrontendUserToHubspotEvent(
            $this,
            $this->configuration,
            $frontendUser
        );

        CompatibilityUtility::dispatchEvent($beforeAddEvent);

        $this->configuration = $beforeAddEvent->getConfiguration();
        $frontendUser = $beforeAddEvent->getFrontendUser();
        unset($beforeAddEvent);

        try {
            $mappedHubspotProperties = $this->mapFrontendUserToHubspotContactProperties($frontendUser);

            $ignoreOnFrontendUserCreate = GeneralUtility::trimExplode(
                ',',
                $this->configuration['settings.']['synchronize.']['ignoreOnHubspotCreate'],
                true
            );
            foreach ($ignoreOnFrontendUserCreate as $propertyName) {
                unset($mappedHubspotProperties[$propertyName]);
            }

            $hubspotContactIdentifier = $this->hubspotContactRepository->create($mappedHubspotProperties);
        } catch (ExistingContactConflictException $existingContactException) {
            $hubspotContact = $this->hubspotContactRepository->findByEmail($frontendUser['email']);

            if ($hubspotContact !== null) {
                $frontendUser['hubspot_id'] = $hubspotContact['vid'];

                $this->frontendUserRepository->update(
                    $frontendUser['uid'],
                    [
                        'hubspot_id' => $frontendUser['hubspot_id'],
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

        $frontendUser['hubspot_id'] = $hubspotContactIdentifier;

        $this->processedRecords['addedToHubspot'][] = $frontendUser['uid'];

        $afterAddEvent = new AfterAddingFrontendUserToHubspotEvent(
            $this,
            $this->configuration,
            $frontendUser
        );

        $this->configuration = $afterAddEvent->getConfiguration();
    }

    /**
     * Compare a frontend user with the connected hubspot contact and update both based on their respective changes
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws BadRequest
     * @throws DataHandlerErrorException
     */
    public function compareAndUpdateFrontendUserAndHubspotContact(array $frontendUser)
    {
        if (
            $frontendUser['hubspot_id'] === 0
            && $this->configuration['settings.']['synchronize.']['createNewInHubspot']
        ) {
            $this->addFrontendUserToHubspot($frontendUser);
            return;
        }

        if ($frontendUser['hubspot_id'] === 0) {
            return;
        }

        $hubspotContactResolver = new ResolveHubspotContactEvent(
            $this,
            $this->configuration,
            $frontendUser
        );

        CompatibilityUtility::dispatchEvent($hubspotContactResolver);

        $this->configuration = $hubspotContactResolver->getConfiguration();
        $frontendUser = $hubspotContactResolver->getFrontendUser();
        $hubspotContact = $hubspotContactResolver->getHubspotContact()
            ?? $this->hubspotContactRepository->findByIdentifier($frontendUser['hubspot_id']);
        unset($hubspotContactResolver);

        if ($hubspotContact === null) {
            return; // We expect the user has been deleted from Hubspot
        }

        $beforeComparingEvent = new BeforeComparingFrontendUserAndHubspotContactEvent(
            $this,
            $this->configuration,
            $frontendUser,
            $hubspotContact
        );

        CompatibilityUtility::dispatchEvent($beforeComparingEvent);

        $this->configuration = $beforeComparingEvent->getConfiguration();
        $frontendUser = $beforeComparingEvent->getFrontendUser();
        $hubspotContact = $beforeComparingEvent->getHubspotContact();
        unset($beforeComparingEvent);

        $hubspotContactProperties = $hubspotContact['properties'];

        // Make email into a property
        if (!isset($hubspotContactProperties['email'])) {
            foreach ($hubspotContact['identity-profiles'][0]['identities'] as $identity) {
                if ($identity['type'] === 'EMAIL' && $identity['is-primary']) {
                    $hubspotContactProperties['email'] = $identity;
                    break;
                }
            }
        }

        // Get names of properties that are newer in Hubspot than in TYPO3
        $modifiedHubspotProperties = [];
        foreach ($hubspotContactProperties as $propertyName => $property) {
            if (
                $this->getLatestMillisecondTimestampFromHubspotProperty($property) > $frontendUser['tstamp'] * 1000
                && $this->getLatestMillisecondTimestampFromHubspotProperty($property) > $frontendUser['hubspot_sync_timestamp'] * 1000
            ) {
                $modifiedHubspotProperties[] = $propertyName;
            }
        }

        $mappedHubspotProperties = $this->mapFrontendUserToHubspotContactProperties($frontendUser);

        foreach ($mappedHubspotProperties as $propertyName => $value) {
            // Remove hubspot properties that are newer in Hubspot so we don't overwrite them in hubspot
            // Remove hubspot properties if there is no changed content
            if (in_array($propertyName, $modifiedHubspotProperties) || $value === $hubspotContactProperties[$propertyName]['value']) {
                unset($mappedHubspotProperties[$propertyName]);
            }

            // Remove hubspot properties that are older in Hubspot so we don't write them to frontend user
            if (!in_array($propertyName, $modifiedHubspotProperties)) {
                if (isset($mappedHubspotProperties[$propertyName])) {
                    $hubspotContact['properties'][$propertyName]['value'] = $mappedHubspotProperties[$propertyName];
                }
            }
        }

        $mappedFrontendUserProperties = $this->mapHubspotContactToFrontendUserProperties($hubspotContact);

        // Remove unchanged properties
        foreach ($mappedFrontendUserProperties as $propertyName => $value) {
            // Remove if value is unchanged
            if ($value === $frontendUser[$propertyName]) {
                unset($mappedFrontendUserProperties[$propertyName]);
            }
        }

        // Remove ignored fields on update
        $ignoreOnFrontendUserUpdate = GeneralUtility::trimExplode(
            ',',
            $this->configuration['settings.']['synchronize.']['ignoreOnFrontendUserUpdate'] ?? '',
            true
        );
        foreach ($ignoreOnFrontendUserUpdate as $propertyName) {
            unset($mappedFrontendUserProperties[$propertyName]);
        }

        if ($frontendUser['hubspot_created_timestamp'] !== 0) {
            unset($mappedFrontendUserProperties['hubspot_created_timestamp']);
        }

        $ignoreOnHubspotUpdate = GeneralUtility::trimExplode(
            ',',
            $this->configuration['settings.']['synchronize.']['ignoreOnHubspotUpdate'] ?? '',
            true
        );
        foreach ($ignoreOnHubspotUpdate as $propertyName) {
            unset($mappedHubspotProperties[$propertyName]);
        }

        $beforeUpdatingEvent = new BeforeUpdatingFrontendUserAndHubspotContactEvent(
            $this,
            $this->configuration,
            $frontendUser,
            $hubspotContact,
            $mappedFrontendUserProperties,
            $mappedHubspotProperties
        );

        CompatibilityUtility::dispatchEvent($beforeUpdatingEvent);

        $this->configuration = $beforeUpdatingEvent->getConfiguration();
        $frontendUser = $beforeUpdatingEvent->getFrontendUser();
        $hubspotContact = $beforeUpdatingEvent->getHubspotContact();
        $mappedFrontendUserProperties = $beforeUpdatingEvent->getMappedFrontendUserProperties();
        $mappedHubspotProperties = $beforeUpdatingEvent->getMappedHubspotContactProperties();
        unset($beforeUpdatingEvent);

        if (count($mappedFrontendUserProperties) > 0) {
            $this->frontendUserRepository->update($frontendUser['uid'], $mappedFrontendUserProperties);
            $this->processedRecords['modifiedInHubspot'][] = $frontendUser['uid'];
        } else {
            $this->frontendUserRepository->setSyncPassSilently($frontendUser['uid']);
            $this->processedRecords['frontendUsersNotSynchronized'][] = $frontendUser['uid'];
        }

        if (count($mappedHubspotProperties) > 0) {
            $this->hubspotContactRepository->update($frontendUser['hubspot_id'], $mappedHubspotProperties);
            $this->processedRecords['modifiedInFrontendUsers'][] = $frontendUser['uid'];
        }

        $afterUpdatingEvent = new AfterUpdatingFrontendUserAndHubspotContactEvent(
            $this,
            $this->configuration,
            $frontendUser,
            $hubspotContact,
            $mappedFrontendUserProperties,
            $mappedHubspotProperties
        );

        $this->configuration = $afterUpdatingEvent->getConfiguration();
    }

    /**
     * Maps a frontend user to hubspot properties
     *
     * Uses configuration from module.tx_hubspot.settings.synchronize.toHubspot
     *
     * @param array $frontendUser
     * @return array
     */
    public function mapFrontendUserToHubspotContactProperties(array $frontendUser): array
    {
        $beforeMappingEvent = new BeforeMappingFrontendUserToHubspotContactEvent(
            $this,
            $this->configuration,
            $frontendUser
        );

        CompatibilityUtility::dispatchEvent($beforeMappingEvent);

        $this->configuration = $beforeMappingEvent->getConfiguration();
        $frontendUser = $beforeMappingEvent->getFrontendUser();
        unset($beforeMappingEvent);

        $toHubspot = $this->configuration['settings.']['synchronize.']['toHubspot.'] ?? [];

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($frontendUser);

        $hubspotProperties = [];
        foreach (array_keys($toHubspot) as $hubspotProperty) {
            $hubspotProperty = rtrim($hubspotProperty, '.');

            if (array_key_exists($hubspotProperty, $hubspotProperties)) {
                continue;
            }

            $hubspotProperties[$hubspotProperty] = $contentObjectRenderer->stdWrap(
                $toHubspot[$hubspotProperty] ?? '',
                $toHubspot[$hubspotProperty . '.'] ?? []
            );
        }

        $hubspotProperties = ArrayUtility::removeNullValuesRecursive($hubspotProperties);

        $afterMappingEvent = new AfterMappingFrontendUserToHubspotContactPropertiesEvent(
            $this,
            $this->configuration,
            $frontendUser,
            $hubspotProperties
        );

        CompatibilityUtility::dispatchEvent($afterMappingEvent);

        $this->configuration = $afterMappingEvent->getConfiguration();

        return $afterMappingEvent->getHubspotProperties();
    }

    /**
     * Maps a hubspot contact to frontend user properties
     *
     * Uses configuration from module.tx_hubspot.settings.synchronize.toFrontendUser
     *
     * @param array $hubspotContact
     * @return array Frontend User row
     */
    public function mapHubspotContactToFrontendUserProperties(array $hubspotContact): array
    {
        $beforeMappingEvent = new BeforeMappingHubspotContactToFrontendUserEvent(
            $this,
            $this->configuration,
            $hubspotContact
        );

        CompatibilityUtility::dispatchEvent($beforeMappingEvent);

        $this->configuration = $beforeMappingEvent->getConfiguration();
        $hubspotContact = $beforeMappingEvent->getHubspotContact();
        unset($beforeMappingEvent);

        $hubspotContactProperties = [];

        // Flatten the properties
        foreach ($hubspotContact['properties'] as $key => $property) {
            $hubspotContactProperties[$key] = $property['value'];
        }

        // Make email into a property
        if (!isset($hubspotContactProperties['email'])) {
            foreach ($hubspotContact['identity-profiles'][0]['identities'] as $identity) {
                if ($identity['type'] === 'EMAIL' && $identity['is-primary']) {
                    $hubspotContactProperties['email'] = $identity['value'];
                    break;
                }
            }
        }

        $toFrontendUser = $this->configuration['settings.']['synchronize.']['toFrontendUser.'] ?? [];

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($hubspotContactProperties);

        $frontendUserProperties = [];
        foreach (array_keys($toFrontendUser) as $frontendUserProperty) {
            $frontendUserProperty = rtrim($frontendUserProperty, '.');

            if (array_key_exists($frontendUserProperty, $frontendUserProperties)) {
                continue;
            }

            $frontendUserProperties[$frontendUserProperty] = $contentObjectRenderer->stdWrap(
                $hubspotContactProperties[$toFrontendUser[$frontendUserProperty] ?? ''] ?? '',
                $toFrontendUser[$frontendUserProperty . '.'] ?? []
            );
        }

        $frontendUserProperties['hubspot_created_timestamp'] =
            $hubspotContactProperties['createdate'] ?? $hubspotContact['addedAt'] ?? 0; // Millisecond timestamp
        $frontendUserProperties['hubspot_id'] = $hubspotContact['vid'];

        $frontendUserProperties = ArrayUtility::removeNullValuesRecursive($frontendUserProperties);

        $afterMappingEvent = new AfterMappingHubspotContactToFrontendUserEvent(
            $this,
            $this->configuration,
            $hubspotContact,
            $frontendUserProperties
        );

        CompatibilityUtility::dispatchEvent($afterMappingEvent);

        $this->configuration = $afterMappingEvent->getConfiguration();

        return $afterMappingEvent->getFrontendUserProperties();
    }

    /**
     * Parses a hubspot property, returning the last modification timestamp from the history sub property
     *
     * @param array $hubspotProperty
     * @return int Millisecond timestamp
     */
    protected function getLatestMillisecondTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        return $hubspotProperty['versions'][0]['timestamp'] ?? $hubspotProperty['timestamp'];
    }
}
