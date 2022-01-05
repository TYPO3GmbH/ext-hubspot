<?php

declare(strict_types=1);


namespace T3G\Hubspot\Repository;

use T3G\Hubspot\Hubspot\Factory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for Hubspot custom object definitions.
 */
class HubspotCustomObjectSchemaRepository extends AbstractHubspotRepository
{
    /**
     * Key for custom objects in the TYPO3 system registry.
     */
    protected const REGISTRY_CUSTOM_OBJECT_SCHEMA = 'customObjectSchema';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Factory|null $factory
     * @param Registry|null $registry
     */
    public function __construct(Factory $factory = null, Registry $registry = null)
    {
        parent::__construct($factory);

        $this->registry = $registry ?? GeneralUtility::makeInstance(Registry::class);
    }

    /**
     * Find all custom object schemas.
     *
     * @param bool $cached If true, use the local list of schemas in registry. If false, query Hubspot.
     * @return array of custom object schemas
     */
    public function findAll(bool $cached = true): array
    {
        $schemaLabels = $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA, null);

        if (!$cached || !is_array($schemaLabels)) {
            $schemas = $this->fetchAllSchemasFromHubspot();

            $schemaLabels = [];
            foreach ($schemas as $schema) {
                $this->registry->set(
                    'hubspot',
                    self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $schema['name'],
                    $schema
                );

                $schemaLabels[$schema['name']] = $schema['labels']['singular'];
            }

            $this->registry->set(
                'hubspot',
                self::REGISTRY_CUSTOM_OBJECT_SCHEMA,
                $schemaLabels
            );

            return $schemas;
        }

        $schemas = [];
        foreach (array_keys($schemaLabels) as $schemaName) {
            $schemas[$schemaName] = $this->registry->get(
                'hubspot',
                self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $schemaName
            );
        }

        return $schemas;
    }

    /**
     * Get singular labels for schemas.
     *
     * @param bool $cached If true, use the local list of schemas in registry. If false, query Hubspot.
     * @return array of schema labels [name => singular label]
     */
    public function findAllLabels($cached = true): array
    {
        $schemaLabels = $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA);

        if (!$cached || !is_array($schemaLabels)) {
            $this->findAll(false);

            $schemaLabels = $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA);
        }

        return $schemaLabels;
    }

    /**
     * Get names for schemas.
     *
     * @param bool $cached If true, use the local list of schemas in registry. If false, query Hubspot.
     * @return array of schema names
     */
    public function findAllNames($cached = true): array
    {
        return array_keys($this->findAllLabels($cached));
    }

    /**
     * Get a specific schema by its name.
     *
     * @param string $name
     * @param bool $cached If true, use the local list of schemas in registry. If false, query Hubspot.
     * @return array|null The custom object schema or null if no schema with $name exists.
     */
    public function findByName(string $name, $cached = true): ?array
    {
        $schema = $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $name);

        if (!$cached || !is_array($schema)) {
            $schema = $this->findAll()[$name] ?? null;
        }

        return $schema;
    }

    /**
     * Fetch all schemas from Hubspot.
     *
     * @return array of hubspot custom object schemas.
     */
    protected function fetchAllSchemasFromHubspot(): array
    {
        return $this->factory->customObjectSchemas()->all()->toArray()['results'] ?? [];
    }

}
