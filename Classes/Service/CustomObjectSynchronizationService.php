<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

use T3G\Hubspot\Domain\Repository\Database\MappedTableRepository;
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
                SchemaUtility::makeFullyQualifiedName($synchronizationConfiguration['objectName'])
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

    protected function addRecordToHubspot(array $record)
    {
        $mappedData = $this->mapRecordToHubspot($record);

        $objectId = $this->customObjectRepository->create($mappedData);

        $this->mappedTableRepository->add($objectId, $record['uid']);
    }

    /**
     * @param array $record
     * @param bool $update If true, the mapping is done for updating. Otherwise for create.
     * @return array
     */
    protected function mapRecordToHubspot(array $record, bool $isUpdate = false)
    {
        $currentSchema = $this->customObjectSchemaRepository->findByName($this->getCurrentObjectName());

        $hubspotPropertyNames = $this->getPropertyNamesFromProperties($currentSchema['properties']);

        $toHubspot = $this->getCurrentSynchronizationConfiguration()['toHubspot.'] ?? [];

        $ignoreFieldsPropertyName = $isUpdate ? 'ignoreOnHubspotUpdate' : 'ignoreOnHubspotCreate';
        $ignoreFields = GeneralUtility::trimExplode(
            ',',
            $this->getCurrentSynchronizationConfiguration()[$ignoreFieldsPropertyName] ?? '',
            true
        );

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($record);

        $hubspotProperties = [];
        foreach (array_keys($toHubspot) as $hubspotProperty) {
            $hubspotProperty = rtrim($hubspotProperty, '.');

            if (
                array_key_exists($hubspotProperty, $hubspotProperties)
                || !in_array($hubspotProperty, $hubspotPropertyNames)
                || in_array($hubspotProperty, $ignoreFields)
            ) {
                continue;
            }

            $hubspotProperties[$hubspotProperty] = $toHubspot[$hubspotProperty];
        }

        $hubspotProperties = ArrayUtility::removeNullValuesRecursive($hubspotProperties);

        $mappedData = [];
        foreach ($hubspotProperties as $hubspotProperty => $localFieldName) {
            $mappedData[$hubspotProperty] = $contentObjectRenderer->stdWrap(
                $record[$localFieldName],
                $toHubspot[$hubspotProperty . '.'] ?? []
            );
        }

        return $mappedData;
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
        $toLocal = $this->getCurrentSynchronizationConfiguration()['toLocal.'] ?? [];

        $localPropertyNames = array_keys($GLOBALS['TCA'][$this->getCurrentTableName()]['columns']);

        $ignoreFieldsPropertyName = $isUpdate ? 'ignoreOnLocalUpdate' : 'ignoreOnLocalCreate';
        $ignoreFields = GeneralUtility::trimExplode(
            ',',
            $this->getCurrentSynchronizationConfiguration()[$ignoreFieldsPropertyName] ?? ''
        );

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start($properties);

        $localProperties = [];
        foreach (array_keys($toLocal) as $localPeroperty) {
            $localPeroperty = rtrim($localPeroperty, '.');

            if (
                array_key_exists($localPeroperty, $localProperties)
                || !in_array($localPeroperty, $localPropertyNames)
                || in_array($localPeroperty, $ignoreFields)
            ) {
                continue;
            }

            $localProperties[$localPeroperty] = $contentObjectRenderer->stdWrap(
                $toLocal[$localPeroperty] ?? '',
                $toLocal[$localPeroperty . '.'] ?? []
            );
        }

        $localProperties = ArrayUtility::removeNullValuesRecursive($localProperties);

        $mappedData = [];
        foreach ($localProperties as $localProperty => $hubspotPropertyName) {
            $mappedData[$localProperty] = $properties[$hubspotPropertyName];
        }

        return $mappedData;
    }

    /**
     * @param array $record
     */
    protected function synchronizeRecord(array $record)
    {
        $mappedRecordProperties = $this->mapRecordToHubspot($record, true);

        $hubspotData = $this->customObjectRepository->get($record['hubspot_id']);

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
