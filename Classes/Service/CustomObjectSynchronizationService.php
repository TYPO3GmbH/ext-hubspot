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

            if ($synchronizationConfiguration['createNewInTypo3']) {
                // TODO: Fetch existing from Hubspot
            }

            if ($synchronizationConfiguration['createNewInHubspot']) {
                $records = $this->mappedTableRepository->findNotYetSynchronized();

                foreach ($records as $record) {
                    // TODO: Check if the record exists already using some unique value.

                    $this->addRecordToHubspot($record);
                }
            }

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
     * Check if a representation of $record exists as an object in Hubspot using the unique
     *
     * @param array $record
     * @return int The object ID or zero if none found.
     */
    protected function findObjectWithUniqueValueInHubspot(array $record): int
    {

    }

    /**
     * Connect an existing TYPO3 record with an existing Hubspot custom object.
     *
     * @param array $record
     * @param int $hubspotId
     */
    protected function connectRecordToHubspot(int $recordId, int $hubspotId)
    {

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

            // TODO: If has existing associations

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

            $this->customObjectRepository->addAssociation(
                $fromObjectId,
                $toObjectType,
                $toObjectId,
                $fromObjectType . '_to_' . $toObjectType
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
        $mappedRecordProperties = $this->mapRecordToHubspot($record, true);

        $hubspotData = $this->customObjectRepository->findById($record['hubspot_id']);

        $mappedObjectProperties = $this->mapHubspotToRecord($hubspotData['properties'], true);


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
            $properties = $this->removeInternalPropertyNames($properties);
        }

        return array_column($properties, 'name');
    }

    /**
     * Given an array of Hubspot property definition, this method removes any internal Hubspot properties (hs_*).
     *
     * @param array $properties
     * @return array
     */
    protected function removeInternalPropertyNames(array $properties): array
    {
        return array_filter(
            $properties,
            function ($property) {
                return strpos($property['name'], 'hs_') !== 0;
            }
        );
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
}
