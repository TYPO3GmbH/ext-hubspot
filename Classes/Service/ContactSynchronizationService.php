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
use T3G\Hubspot\Repository\Exception\HubspotExistingContactConflictException;
use T3G\Hubspot\Repository\Exception\UnexpectedMissingContactException;
use T3G\Hubspot\Repository\HubspotContactRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use T3G\Hubspot\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
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

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher = null;

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
    public function __construct()
    {
        $this->hubspotContactRepository = GeneralUtility::makeInstance(HubspotContactRepository::class);
        $this->frontendUserRepository = GeneralUtility::makeInstance(FrontendUserRepository::class);
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class);
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

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before'
        );

        if ($this->configuration['settings.']['synchronize.']['createNewInTypo3']) {
            $newHubspotContacts = $this->hubspotContactRepository->findNewBefore(
                $this->frontendUserRepository->getOldestHubspotCreatedTimestamp()
            );

            if (count($newHubspotContacts) > 0) {
                foreach ($newHubspotContacts as $newHubspotContact) {
                    $this->addHubspotContactToFrontendUsers($newHubspotContact);
                }

                return; // Do more on next run
            }
        }

        $frontendUsers = $this->frontendUserRepository->findNotYetSynchronized();

        if (count($frontendUsers) > 0) {
            $this->frontendUserRepository->fixSyncPassIdentifierScope();

            foreach ($frontendUsers as $frontendUser) {
                $this->synchronizeFrontendUser($frontendUser);
            }
        }

        $frontendUsers = $this->frontendUserRepository->findReadyForSyncPass();

        foreach ($frontendUsers as $frontendUser) {
            $this->synchronizeFrontendUser($frontendUser);
        }

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-after',
            ['processedRecords' => $this->processedRecords]
        );
    }

    /**
     * Update a frontend user with hubspot contact or create a new hubspot contact based on the frontend user
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \T3G\Hubspot\Repository\Exception\DataHandlerErrorException
     */
    public function synchronizeFrontendUser(array $frontendUser)
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['frontendUser' => $frontendUser]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;

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

       $this->frontendUserRepository->setSearchPids(
           GeneralUtility::intExplode(',', $this->configuration['synchronize.']['limitToPids'] ?? '', true)
       );
    }

    /**
     * Use a hubspot contact to create a new frontend user
     *
     * @param array $hubspotContact
     * @throws \T3G\Hubspot\Repository\Exception\DataHandlerErrorException
     */
    protected function addHubspotContactToFrontendUsers(array $hubspotContact)
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['hubspotContact' => $hubspotContact]
        );
        $hubspotContact = $signalArguments['hubspotContact'] ?? $hubspotContact;

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

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-after',
            [
                'hubspotContact' => $hubspotContact,
                'frontendUserIdentifier' => $frontendUserIdentifier
            ]
        );
    }

    /**
     * Use a frontend user to create a hubspot user
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \T3G\Hubspot\Repository\Exception\DataHandlerErrorException
     */
    protected function addFrontendUserToHubspot(array $frontendUser)
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['frontendUser' => $frontendUser]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;

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
        } catch (HubspotExistingContactConflictException $existingContactException) {
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

                $signalArguments = $this->dispatchSignal(
                    __FUNCTION__ . '-afterSynchronize',
                    ['frontendUser' => $frontendUser]
                );

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

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-after',
            ['frontendUser' => $frontendUser]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;

        $this->processedRecords['addedToHubspot'][] = $frontendUser['uid'];
    }

    /**
     * Compare a frontend user with the connected hubspot contact and update both based on their respective changes
     *
     * @param array $frontendUser
     * @throws UnexpectedMissingContactException
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \T3G\Hubspot\Repository\Exception\DataHandlerErrorException
     */
    protected function compareAndUpdateFrontendUserAndHubspotContact(array $frontendUser)
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['frontendUser' => $frontendUser]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;

        if (
            $frontendUser['hubspot_id'] === 0
            && $this->configuration['settings.']['synchronize.']['createNewInHubspot']
        ) {
            $this->addFrontendUserToHubspot($frontendUser);
            return;
        } elseif ($frontendUser['hubspot_id'] === 0) {
            return;
        }

        $hubspotContact = $this->hubspotContactRepository->findByIdentifier($frontendUser['hubspot_id']);

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-findHubspotContact',
            [
                'frontendUser' => $frontendUser,
                'hubspotContact' => $hubspotContact
            ]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;
        $hubspotContact = $signalArguments['hubspotContact'] ?? $hubspotContact;

        if ($hubspotContact === null) {
            return; // We expect the user has been deleted from Hubspot
        }

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
            $this->configuration['settings.']['synchronize.']['ignoreOnFrontendUserUpdate'],
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
            $this->configuration['settings.']['synchronize.']['ignoreOnHubspotUpdate'],
            true
        );
        foreach ($ignoreOnHubspotUpdate as $propertyName) {
            unset($mappedHubspotProperties[$propertyName]);
        }

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-beforePersist',
            [
                'frontendUser' => $frontendUser,
                'hubspotContact' => $hubspotContact,
                'mappedFrontendUserProperties' => $mappedFrontendUserProperties,
                'mappedHubspotProperties' => $mappedHubspotProperties,
            ]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;
        $mappedFrontendUserProperties = $signalArguments['mappedFrontendUserProperties'] ?? $mappedFrontendUserProperties;
        $mappedHubspotProperties = $signalArguments['mappedHubspotProperties'] ?? $mappedHubspotProperties;

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

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-afterPersist',
            [
                'frontendUser' => $frontendUser,
                'hubspotContact' => $hubspotContact,
                'mappedFrontendUserProperties' => $mappedFrontendUserProperties,
                'mappedHubspotProperties' => $mappedHubspotProperties,
            ]
        );
    }

    /**
     * Maps a frontend user to hubspot properties
     *
     * Uses configuration from module.tx_hubspot.settings.synchronize.toHubspot
     *
     * @param array $frontendUser
     * @return array
     */
    protected function mapFrontendUserToHubspotContactProperties(array $frontendUser): array
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['frontendUser' => $frontendUser]
        );
        $frontendUser = $signalArguments['frontendUser'] ?? $frontendUser;

        $toHubspot = $this->configuration['settings.']['synchronize.']['toHubspot.'];

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($frontendUser);

        $hubspotProperties = [];
        foreach (array_keys($toHubspot) as $hubspotProperty) {
            $hubspotProperty = rtrim($hubspotProperty, '.');

            if (key_exists($hubspotProperty, $hubspotProperties)) {
                continue;
            }

            $hubspotProperties[$hubspotProperty] = $contentObjectRenderer->stdWrap(
                $toHubspot[$hubspotProperty] ?? '',
                $toHubspot[$hubspotProperty . '.'] ?? []
            );
        }

        $hubspotProperties = ArrayUtility::removeNullValuesRecursive($hubspotProperties);

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-after',
            [
                'frontendUser' => $frontendUser,
                'hubspotProperties' => $hubspotProperties
            ]
        );
        $hubspotProperties = $signalArguments['hubspotProperties'] ?? $hubspotProperties;

        return $hubspotProperties;
    }

    /**
     * Maps a hubspot contact to frontend user properties
     *
     * Uses configuration from module.tx_hubspot.settings.synchronize.toFrontendUser
     *
     * @param array $hubspotContact
     * @return array Frontend User row
     */
    protected function mapHubspotContactToFrontendUserProperties(array $hubspotContact): array
    {
        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-before',
            ['hubspotContact' => $hubspotContact]
        );
        $hubspotContact = $signalArguments['hubspotContact'] ?? $hubspotContact;

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

        $toFrontendUser = $this->configuration['settings.']['synchronize.']['toFrontendUser.'];

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($hubspotContactProperties);

        $frontendUserProperties = [];
        foreach (array_keys($toFrontendUser) as $frontendUserProperty) {
            $frontendUserProperty = rtrim($frontendUserProperty, '.');

            if (key_exists($frontendUserProperty, $frontendUserProperties)) {
                continue;
            }

            $frontendUserProperties[$frontendUserProperty] = $contentObjectRenderer->stdWrap(
                $hubspotContactProperties[$toFrontendUser[$frontendUserProperty]] ?? '',
                $toFrontendUser[$frontendUserProperty . '.'] ?? []
            );
        }

        $frontendUserProperties['hubspot_created_timestamp'] =
            $hubspotContactProperties['createdate'] ?? $hubspotContact['addedAt']; // Millisecond timestamp
        $frontendUserProperties['hubspot_id'] = $hubspotContact['vid'];

        $frontendUserProperties = ArrayUtility::removeNullValuesRecursive($frontendUserProperties);

        $signalArguments = $this->dispatchSignal(
            __FUNCTION__ . '-after',
            [
                'frontendUserProperties' => $frontendUserProperties,
                'hubspotContact' => $hubspotContact
            ]
        );
        $frontendUserProperties = $signalArguments['hubspotProperties'] ?? $frontendUserProperties;

        return $frontendUserProperties;
    }

    /**
     * @param string $name
     * @param array $signalArguments
     * @return array
     */
    protected function dispatchSignal(string $name, array $signalArguments = []): array
    {
        $originalSignalArguments = $signalArguments;

        $signalArguments['configuration'] = $this->configuration;
        $signalArguments['caller'] = $this;

        $signalArguments = (array)$this->signalSlotDispatcher->dispatch(
            __CLASS__,
            $name,
            $signalArguments
        );

        $this->configuration = $signalArguments['configuration'] ?? $this->configuration;

        unset($signalArguments['configuration']);
        unset($signalArguments['caller']);

        foreach ($originalSignalArguments as $key => $originalValue) {
            $signalArguments[$key] = $signalArguments[$key] ?? $originalSignalArguments[$key];
        }

        return $signalArguments;
    }

    /**
     * Parses a hubspot property, returning the last modification timestamp from the history sub property
     *
     * @param array $hubspotProperty
     * @return int Millisecond timestamp
     */
    protected function getLatestMillisecondTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        return $hubspotProperty['versions'][0]['timestamp'];
    }
}
