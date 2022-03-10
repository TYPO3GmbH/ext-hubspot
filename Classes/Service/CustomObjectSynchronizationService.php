<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

use T3G\Hubspot\Domain\Repository\Database\FrontendUserRepository;
use T3G\Hubspot\Domain\Repository\Database\MappedTableRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectSchemaRepository;
use T3G\Hubspot\Service\Event\AfterAddingMappedTableRecordToHubspotEvent;
use T3G\Hubspot\Service\Event\BeforeAddingMappedTableRecordToHubspotEvent;
use T3G\Hubspot\Service\Event\BeforeCustomObjectSynchronizationEvent;
use T3G\Hubspot\Service\Exception\SkipRecordSynchronizationException;
use T3G\Hubspot\Service\Exception\StopRecordSynchronizationException;
use T3G\Hubspot\Utility\CompatibilityUtility;
use T3G\Hubspot\Utility\CustomObjectUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Synchronization service for Hubspot custom objects
 */
class CustomObjectSynchronizationService extends AbstractSynchronizationService
{
    /**
     * @var CustomObjectRepository
     */
    protected $customObjectRepository;

    /**
     * @var CustomObjectSchemaRepository
     */
    protected $customObjectSchemaRepository;

    /**
     * @var MappedTableRepository
     */
    protected $mappedTableRepository;

    /**
     * @var array
     */
    protected $currentSynchronizationConfiguration;

    /**
     * @param CustomObjectRepository|null $customObjectRepository
     */
    public function __construct(
        CustomObjectSchemaRepository $customObjectSchemaRepository = null
    )
    {
        $this->customObjectSchemaRepository = $customObjectSchemaRepository
            ?? GeneralUtility::makeInstance(CustomObjectSchemaRepository::class);
    }

    public function synchronize(int $defaultPid = null)
    {
        $this->configureForPageId(
            $defaultPid
            ?? (int)$this->defaultConfiguration['persistence.']['synchronizeCustomObjects.']['defaultPid']
        );

        if (count($this->configuration['settings.']['synchronizeCustomObjects.'] ?? []) === 0) {
            return;
        }

        $beforeSynchronizationEvent = new BeforeCustomObjectSynchronizationEvent(
            $this,
            $this->configuration
        );

        try {
            CompatibilityUtility::dispatchEvent($beforeSynchronizationEvent);
        } catch (StopRecordSynchronizationException $e) {
            $this->logInfo('Stopped record synchronization: ' . $e->getMessage());

            return;
        }

        $this->configuration = $beforeSynchronizationEvent->getConfiguration();
        unset($beforeSynchronizationEvent);

        $synchronizationDefaults = $this->configuration['settings.']['synchronizeCustomObjects.']['*.'] ?? [];

        foreach ($this->configuration['settings.']['synchronizeCustomObjects.'] as $typoScriptKey => $synchronizationConfiguration) {
            if (!is_array($synchronizationConfiguration) || $typoScriptKey === '*.') {
                continue;
            }

            $typoScriptKey = substr($typoScriptKey, 0, -1); // Remove trailing dot

            $this->currentSynchronizationConfiguration = array_replace_recursive(
                $synchronizationDefaults,
                $synchronizationConfiguration
            );

            $this->customObjectRepository = GeneralUtility::makeInstance(
                CustomObjectRepository::class,
                $synchronizationConfiguration['objectName']
            );

            $this->mappedTableRepository = GeneralUtility::makeInstance(
                MappedTableRepository::class,
                $this->getCurrentObjectName(),
                $typoScriptKey,
                $this->getCurrentTableName()
            );

            $this->configureRepositoryDefaults();

            //if ($synchronizationConfiguration['createNewInTypo3']) {
            //    // Fetch existing from Hubspot. How to do this with the v3 API?
            //}

            if ($synchronizationConfiguration['createNewInHubspot']) {
                $records = $this->mappedTableRepository->findNotYetSynchronized();

                $this->logInfo(
                    'Found ' . count($records) . ' TYPO3 records not yet synced (' . $this->getCurrentTableName() . ')'
                    . ' to custom object (' . $this->getCurrentObjectName() . ').'
                );

                foreach ($records as $record) {
                    $idInHubspot = $this->findObjectWithUniqueValueInHubspot($record);

                    if ($idInHubspot > 0) {
                        $this->mappedTableRepository->add($idInHubspot, (int)$record['uid']);

                        continue;
                    }

                    try {
                        $this->addRecordToHubspot($record);
                    } catch (SkipRecordSynchronizationException $e) {
                        $this->mappedTableRepository->add(0, (int)$record['uid']);

                        $this->logInfo(
                            'Skipped when adding record ' . $record['uid']
                            . ' (' . $this->getCurrentTableName() . '): ' . $e->getMessage()
                        );

                        continue;
                    } catch (StopRecordSynchronizationException $e) {
                        $this->mappedTableRepository->add(0, (int)$record['uid']);

                        $this->logInfo(
                            'Stopped when adding record ' . $record['uid']
                            . ' (' . $this->getCurrentTableName() . '): ' . $e->getMessage()
                        );

                        return;
                    }
                }
            }

            $records = $this->mappedTableRepository->findReadyForSyncPass();

            $this->logInfo(
                'Found ' . count($records) . ' TYPO3 records ready for sync (' . $this->getCurrentTableName() . ')'
                . ' to custom object (' . $this->getCurrentObjectName() . ').'
            );

            foreach ($records as $record) {
                try {
                    $this->synchronizeRecord($record);
                } catch (SkipRecordSynchronizationException $e) {
                    $this->mappedTableRepository->setSyncPassSilently($record['uid']);

                    $this->logInfo(
                        'Skipped sync of record ' . $record['uid']
                        . ' (' . $this->getCurrentTableName() . '): ' . $e->getMessage()
                    );

                    continue;
                } catch (StopRecordSynchronizationException $e) {
                    $this->mappedTableRepository->setSyncPassSilently($record['uid']);

                    $this->logInfo(
                        'Stopped when syncing record ' . $record['uid']
                        . ' (' . $this->getCurrentTableName() . '): ' . $e->getMessage()
                    );

                    return;
                }
            }
        }

        return;
    }

    /**
     * Connect a Hubspot object to a record and brag about it in the log.
     *
     * @param int $hubspotId
     * @param int $recordId
     */
    protected function connectHubspotObjectToRecord(int $hubspotId, int $recordId)
    {
        $this->mappedTableRepository->add($hubspotId, $recordId);

        $this->logInfo('Connected record ' . $recordId . ' to existing Hubspot object ' . $hubspotId);
    }

    /**
     * Check if a representation of $record exists as an object in Hubspot using the unique
     *
     * @param array $record
     * @return int The object ID or zero if none found.
     */
    protected function findObjectWithUniqueValueInHubspot(array $record): int
    {
        $uniquePropertyNames = CustomObjectUtility::getNamesOfUniqueProperties($this->getCurrentObjectName());

        $mappedProperties = $this->mapRecordToHubspot($record);

        foreach ($uniquePropertyNames as $uniquePropertyName) {
            if (!isset($mappedProperties[$uniquePropertyName])) {
                continue;
            }

            return (int)($this->customObjectRepository->findByUniqueProperty(
                $uniquePropertyName,
                $mappedProperties[$uniquePropertyName]
            )['id'] ?? 0);
        }

        return 0;
    }

    protected function addRecordToHubspot(array $record)
    {
        $beforeAddEvent = new BeforeAddingMappedTableRecordToHubspotEvent(
            $this,
            $this->configuration,
            $this->mappedTableRepository,
            $record
        );

        CompatibilityUtility::dispatchEvent($beforeAddEvent);

        $this->configuration = $beforeAddEvent->getConfiguration();
        $record = $beforeAddEvent->getMappedTableRecord();
        unset($beforeAddEvent);

        $mappedData = $this->mapRecordToHubspot($record);

        $objectId = $this->customObjectRepository->create($mappedData);

        $this->logInfo(
            'Added record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ') to Hubspot as '
            . $objectId . ' (' . $this->getCurrentObjectName() . ')',
            [
                'record' => $record,
                'mappedData' => $mappedData,
            ]
        );

        $this->resolveAssociationsForObject($objectId, $record);

        $this->mappedTableRepository->add($objectId, $record['uid']);

        $afterAddEvent = new AfterAddingMappedTableRecordToHubspotEvent(
            $this,
            $this->configuration,
            $this->mappedTableRepository,
            $record,
            $objectId
        );

        $this->configuration = $afterAddEvent->getConfiguration();
    }

    /**
     * Resolve, add and update associations between a custom object and another object.
     *
     * @param int $fromObjectId
     * @param array $record
     */
    protected function resolveAssociationsForObject(int $fromObjectId, array $record)
    {
        $associationConfigurations = $this->getCurrentSynchronizationConfiguration()['associations.'] ?? [];

        $transformedValues = $this->mapAndtransformValues($record, $associationConfigurations);

        foreach ($transformedValues as $toObjectType => $toObjectTypo3Id) {
            $existingAssociations = $this->customObjectRepository->findAssociations($fromObjectId, $toObjectType);

            // The extension can handle contacts in addtion to custom objects.
            if ($toObjectType === 'contact') {
                $frontendUserRepository = GeneralUtility::makeInstance(FrontendUserRepository::class);

                $toObjectId = $frontendUserRepository->getHubspotIdById((int)$toObjectTypo3Id);
            // We assume this is a custom object even though other objects exist.
            } else {
                $table = $this->getSynchronizationConfigurationByObjectName($toObjectType)['table'] ?? null;

                if ($table === null) {
                    continue;
                }

                $toObjectId = MappedTableRepository::getHubspotId(
                    $table,
                    $toObjectTypo3Id,
                    $toObjectType
                );
            }

            $fromObjectType = $this->getCurrentObjectName();

            if ($toObjectId === 0) {
                $this->logInfo(
                    'Could not find associated object local ID ' . $toObjectTypo3Id . ' for Hubspot object '
                    . $fromObjectId . ' (' . $fromObjectType . ')'
                );

                continue;
            }

            $associationType = $fromObjectType . '_to_' . $toObjectType;

            foreach ($existingAssociations as $existingAssociation) {
                if (
                    (int)$existingAssociation['id'] === $toObjectId
                    && $existingAssociation['type'] === $associationType
                ) {
                    continue 2;
                }
            }

            $this->customObjectRepository->addAssociation(
                $fromObjectId,
                $toObjectType,
                $toObjectId,
                $associationType
            );

            $this->logInfo(
                'Associated object ' . $fromObjectId . ' (' . $fromObjectType . ') with ' . $toObjectId
                . ' (' . $toObjectType . ')'
            );
        }
    }

    /**
     * Returns the configuration for a specific object name or null if no such configuration exists.
     *
     * @param string $objectName
     * @return array|null
     */
    protected function getSynchronizationConfigurationByObjectName(string $objectName): ?array
    {
        foreach ($this->configuration['settings.']['synchronizeCustomObjects.'] as $configuration) {
            if (!is_array($configuration)) {
                continue;
            }

            if ($configuration['objectName'] === $objectName) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * @param array $record
     * @param bool $update If true, the mapping is done for updating. Otherwise for create.
     * @return array
     */
    protected function mapRecordToHubspot(array $record, bool $isUpdate = false)
    {
        $ignoreFieldsPropertyName = $isUpdate ? 'ignoreOnHubspotUpdate' : 'ignoreOnHubspotCreate';
        $ignoreFields = GeneralUtility::trimExplode(
            ',',
            $this->getCurrentSynchronizationConfiguration()[$ignoreFieldsPropertyName] ?? '',
            true
        );

        return $this->mapAndtransformValues(
            $record,
            $this->getCurrentSynchronizationConfiguration()['toHubspot.'] ?? [],
            $ignoreFields
        );
    }

    /**
     * Maps hubspot object properties to a record.
     *
     * @param array $properties
     * @param bool $isUpdate
     * @return array
     */
    protected function mapHubspotToRecord(array $properties, bool $isUpdate = false)
    {
        $ignoreFieldsPropertyName = $isUpdate ? 'ignoreOnLocalUpdate' : 'ignoreOnLocalCreate';
        $ignoreFields = GeneralUtility::trimExplode(
            ',',
            $this->getCurrentSynchronizationConfiguration()[$ignoreFieldsPropertyName] ?? ''
        );

        return $this->mapAndtransformValues(
            $properties,
            $this->getCurrentSynchronizationConfiguration()['toLocal.'] ?? [],
            $ignoreFields
        );
    }

    /**
     * @param array $record
     */
    protected function synchronizeRecord(array $record)
    {
        $hubspotData = $this->customObjectRepository->findById($record['hubspot_id']);

        $recordFieldsMappedToHubspotProperties = $this->mapRecordToHubspot($record, true);

        $modifiedHubspotProperties = [];
        foreach ($hubspotData['propertiesWithHistory'] as $propertyName => $property) {
            if (
                $this->getLatestTimestampFromHubspotProperty($property) > $record['tstamp']
                && $this->getLatestTimestampFromHubspotProperty($property) > $record['hubspot_sync_timestamp']
            ) {
                $modifiedHubspotProperties[] = $propertyName;
            }
        }

        foreach ($recordFieldsMappedToHubspotProperties as $propertyName => $value) {
            // Remove hubspot properties that are newer in Hubspot so we don't overwrite them in hubspot
            // Remove hubspot properties if there is no changed content
            if (
                !in_array($propertyName, $modifiedHubspotProperties)
                || (string)$value === $hubspotData['properties'][$propertyName]
            ) {
                unset($recordFieldsMappedToHubspotProperties[$propertyName]);
            }

            // Remove hubspot properties that are older in Hubspot, so we don't write them to the local record
            if (in_array($propertyName, $modifiedHubspotProperties)) {
                if (isset($recordFieldsMappedToHubspotProperties[$propertyName])) {
                    $hubspotData['properties'][$propertyName] = $recordFieldsMappedToHubspotProperties[$propertyName];
                }
            }
        }

        $hubspotPropertiesMappedToRecordFields = $this->mapHubspotToRecord($hubspotData['properties'], true);

        // Remove unchanged properties
        foreach ($hubspotPropertiesMappedToRecordFields as $propertyName => $value) {
            // Remove if value is unchanged
            if ($value === $record[$propertyName]) {
                unset($hubspotPropertiesMappedToRecordFields[$propertyName]);
            }
        }

        if (count($hubspotPropertiesMappedToRecordFields) > 0) {
            $this->mappedTableRepository->update($record['uid'], $hubspotPropertiesMappedToRecordFields);

            $this->logInfo(
                'Updated record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ') from Hubspot object '
                . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName() . ')',
                [
                    'record' => $hubspotPropertiesMappedToRecordFields,
                ]
            );
        } else {
            $this->mappedTableRepository->setSyncPassSilently($record['uid']);

            $this->logInfo(
                'No update for record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ') from Hubspot object '
                . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName() . ')'
            );
        }

        if (count($recordFieldsMappedToHubspotProperties) > 0) {
            $this->customObjectRepository->update($record['hubspot_id'], $recordFieldsMappedToHubspotProperties);

            $this->logInfo(
                'Updated Hubspot object ' . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName()
                . ') from record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ')',
                [
                    'object' => $recordFieldsMappedToHubspotProperties,
                ]
            );
        } else {
            $this->logInfo(
                'No update for Hubspot object ' . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName()
                . ') from record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ')'
            );
        }

        $this->resolveAssociationsForObject($record['hubspot_id'], $record);
    }

    /**
     * Returns an array of the property names of the property schemas in $properties.
     *
     * @param array $properties Array of property schemas
     * @param bool $removeInternal Remove internal Hubspot property names
     * @return array
     */
    protected function getPropertyNamesFromProperties(array $properties, bool $removeInternal = true): array
    {
        if ($removeInternal) {
            $properties = CustomObjectUtility::removeHubspotInternalProperties($properties);
        }

        return array_column($properties, 'name');
    }

    /**
     * @return array
     */
    protected function getCurrentSynchronizationConfiguration(): array
    {
        return $this->currentSynchronizationConfiguration;
    }

    /**
     * @return string
     */
    protected function getCurrentTableName(): string
    {
        return $this->currentSynchronizationConfiguration['table'];
    }

    /**
     * @return string
     */
    protected function getCurrentObjectName(): string
    {
        return $this->currentSynchronizationConfiguration['objectName'];
    }

    /**
     * Sets repository default values based on $this->configuration
     */
    protected function configureRepositoryDefaults()
    {
        if (isset($this->customObjectRepository)) {
            if ($this->currentSynchronizationConfiguration['limit'] ?? false) {
                $this->customObjectRepository->setLimit(
                    (int)$this->currentSynchronizationConfiguration['limit'] ?? 0
                );
            }
        }

        if (isset($this->mappedTableRepository)) {
            $this->mappedTableRepository->setDefaultPageId(
                (int)$this->configuration['persistence.']['synchronizeCustomObjects.']['storagePid'] ?? 0
            );

            if ($this->currentSynchronizationConfiguration['limit'] ?? false) {
                $this->mappedTableRepository->setLimit(
                    (int)$this->currentSynchronizationConfiguration['limit']
                );
            }

            $this->mappedTableRepository->setSearchPids(
                GeneralUtility::intExplode(
                    ',',
                    $this->currentSynchronizationConfiguration['limitToPids'] ?? '',
                    true
                )
            );
        }
    }

    /**
     * Parses a hubspot property, returning the last modification timestamp from the history sub property
     *
     * @param array $hubspotProperty
     * @return int Millisecond timestamp
     */
    protected function getLatestTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        $date = \DateTime::createFromFormat(
            \DateTimeInterface::RFC3339_EXTENDED,
            $hubspotProperty[0]['timestamp']
        );

        if ($date === false) {
            $this->logInfo('Timestamp not parsable: ' . $hubspotProperty[0]['timestamp'], $hubspotProperty);

            return 0;
        }

        return $date->getTimestamp();
    }

    /**
     * @return CustomObjectRepository
     */
    public function getCustomObjectRepository(): CustomObjectRepository
    {
        return $this->customObjectRepository;
    }

    /**
     * @param CustomObjectRepository $customObjectRepository
     */
    public function setCustomObjectRepository(CustomObjectRepository $customObjectRepository)
    {
        $this->customObjectRepository = $customObjectRepository;
    }

    /**
     * @return MappedTableRepository
     */
    public function getMappedTableRepository(): MappedTableRepository
    {
        return $this->mappedTableRepository;
    }

    /**
     * @param MappedTableRepository $mappedTableRepository
     */
    public function setMappedTableRepository(MappedTableRepository $mappedTableRepository)
    {
        $this->mappedTableRepository = $mappedTableRepository;
    }
}
