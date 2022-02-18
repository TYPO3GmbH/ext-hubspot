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
use T3G\Hubspot\Domain\Repository\Hubspot\ContactRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectRepository;
use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectSchemaRepository;
use T3G\Hubspot\Utility\CustomObjectUtility;
use T3G\Hubspot\Utility\SchemaUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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

        foreach ($this->configuration['settings.']['synchronizeCustomObjects.'] as $typoScriptKey => $synchronizationConfiguration) {
            if (!is_array($synchronizationConfiguration)) {
                continue;
            }

            $typoScriptKey = substr($typoScriptKey, 0, -1); // Remove trailing dot

            $this->currentSynchronizationConfiguration = $synchronizationConfiguration;

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

//            if ($synchronizationConfiguration['createNewInTypo3']) {
//                // TODO: Fetch existing from Hubspot
//            }
//
//            if ($synchronizationConfiguration['createNewInHubspot']) {
//                $records = $this->mappedTableRepository->findNotYetSynchronized();
//
//                foreach ($records as $record) {
//                    $idInHubspot = $this->findObjectWithUniqueValueInHubspot($record);
//
//                    if ($idInHubspot > 0) {
//                        $this->mappedTableRepository->add($idInHubspot, (int)$record['uid']);
//
//                        continue;
//                    }
//
//                    $this->addRecordToHubspot($record);
//                }
//            }

            $records = $this->mappedTableRepository->findReadyForSyncPass();

            foreach ($records as $record) {
                $this->synchronizeRecord($record);
            }
        }

        // TODO: Iterate through objects that have not yet been synced, then objects with changes

        // TODO: Check if relation exists. How do we deal with relations that do not exist? Sync them in a later pass
        // TODO: or wait with syncing? How do we deal with existing objects in Hubspot that have a relation that is
        // TODO: different to what we have on the TYPO3 side? (Suggestion: we do not sync)

        // TODO: Check if object exists in Hubspot using uniqueFields list (for example product type and serial number).
        // TODO: This is the unique key for the Hubspot object when we don't yet have a Hubspot ID for it.

        // TODO: Write object data to Hubspot

        // TODO: Write relations to Hubspot

        // TODO: Persist Hubspot unique id to database, creating a record in tx_hubspot_object_foreigntable_mm

        // TODO: For later: Synchronize the other way, back to TYPO3.
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

        $mappedObjectProperties = $this->mapRecordToHubspot($record, true);

        $modifiedHubspotProperties = [];
        foreach ($hubspotData['propertiesWithHistory'] as $propertyName => $property) {
            if (
                $this->getLatestTimestampFromHubspotProperty($property) > $record['tstamp']
                && $this->getLatestTimestampFromHubspotProperty($property) > $record['hubspot_sync_timestamp']
            ) {
                $modifiedHubspotProperties[] = $propertyName;
            }
        }

        foreach ($mappedObjectProperties as $propertyName => $value) {
            // Remove hubspot properties that are newer in Hubspot so we don't overwrite them in hubspot
            // Remove hubspot properties if there is no changed content
            if (
                in_array($propertyName, $modifiedHubspotProperties)
                || $value === $hubspotData['properties'][$propertyName]
            ) {
                unset($mappedObjectProperties[$propertyName]);
            }

            // Remove hubspot properties that are older in Hubspot so we don't write them to the local record
            if (!in_array($propertyName, $modifiedHubspotProperties)) {
                if (isset($mappedObjectProperties[$propertyName])) {
                    $hubspotData['properties'][$propertyName] = $mappedObjectProperties[$propertyName];
                }
            }
        }

        $mappedRecordProperties = $this->mapHubspotToRecord($hubspotData['properties'], true);

        // Remove unchanged properties
        foreach ($mappedRecordProperties as $propertyName => $value) {
            // Remove if value is unchanged
            if ($value === $record[$propertyName]) {
                unset($mappedRecordProperties[$propertyName]);
            }
        }

        if (count($mappedRecordProperties) > 0) {
            $this->mappedTableRepository->update($record['uid'], $mappedRecordProperties);

            $this->logInfo(
                'Updated record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ') to Hubspot object '
                . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName() . ')',
                [
                    'record' => $mappedRecordProperties,
                ]
            );
        } else {
            $this->mappedTableRepository->setSyncPassSilently($record['uid']);

            $this->logInfo(
                'No update for record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ') to Hubspot object '
                . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName() . ')'
            );
        }

        if (count($mappedObjectProperties) > 0) {
            $this->customObjectRepository->update($record['hubspot_id'], $mappedObjectProperties);

            $this->logInfo(
                'Updated Hubspot object ' . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName()
                . ') to record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ')',
                [
                    'object' => $mappedObjectProperties,
                ]
            );
        } else {
            $this->logInfo(
                'No update for Hubspot object ' . $record['hubspot_id'] . ' (' . $this->getCurrentObjectName()
                . ') to record ' . $record['uid'] . ' (' . $this->getCurrentTableName() . ')'
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
        if (!isset($this->mappedTableRepository)) {
            return;
        }

        $this->mappedTableRepository->setDefaultPageId((int)$this->configuration['persistence.']['synchronizeCustomObjects.']['storagePid']);

        if ($this->configuration['synchronize.']['limit']) {
            $this->mappedTableRepository->setLimit((int)$this->configuration['synchronizeCustomObjects.']['limit']);
        }

        $this->mappedTableRepository->setSearchPids(
            GeneralUtility::intExplode(',', $this->configuration['synchronizeCustomObjects.']['limitToPids'] ?? '', true)
        );
    }

    /**
     * Parses a hubspot property, returning the last modification timestamp from the history sub property
     *
     * @param array $hubspotProperty
     * @return int Millisecond timestamp
     */
    protected function getLatestTimestampFromHubspotProperty(array $hubspotProperty): int
    {
        return \DateTime::createFromFormat(
            \DateTimeInterface::RFC3339_EXTENDED,
            $hubspotProperty[0]['timestamp']
        )->getTimestamp();
    }
}
