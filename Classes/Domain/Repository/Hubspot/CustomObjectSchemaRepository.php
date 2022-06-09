<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Repository\Hubspot;

use T3G\Hubspot\Domain\Model\Hubspot\Dto\ImmutableSchema;
use T3G\Hubspot\Domain\Model\Hubspot\Dto\MutableSchema;
use T3G\Hubspot\Domain\Repository\Hubspot\AbstractHubspotRepository;
use T3G\Hubspot\Hubspot\Factory;
use T3G\Hubspot\Utility\SchemaUtility;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for Hubspot custom object definitions.
 */
class CustomObjectSchemaRepository extends AbstractHubspotRepository
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
        $schemaLabels = $this->getSchemaLabelsFromRegistry();

        if (!$cached || !is_array($schemaLabels)) {
            $schemas = $this->fetchAllSchemasFromHubspot();

            foreach ($schemas as $schema) {
                $this->persistSchemaInRegistry($schema);
            }

            return $schemas;
        }

        $schemas = [];
        foreach (array_keys($schemaLabels ?? $this->getSchemaLabelsFromRegistry() ?? []) as $schemaName) {
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
    public function findAllLabels(bool $cached = true): array
    {
        $schemaLabels = $this->getSchemaLabelsFromRegistry();

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
    public function findAllNames(bool $cached = true): array
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
    public function findByName(string $name, bool $cached = true): ?array
    {
        $schema = $this->getSchemaFromRegistry($name);

        if (!$cached || !is_array($schema)) {
            $schema = $this->findAll(false)[$name] ?? null;
        }

        return $schema;
    }

    /**
     * Create a schema.
     *
     * @param string $name
     * @param array $schema
     * @return string The schema name
     */
    public function create(array $schema): string
    {
        $immutableSchema = new ImmutableSchema();
        $immutableSchema->populate($schema);

        $response = $this->factory->customObjectSchemas()->create($immutableSchema->toArray());

        $this->findAll(false);

        return $response['name'];
    }

    /**
     * Update an existing schema.
     *
     * @param string $name
     * @param array $schema
     */
    public function update(string $name, array $schema)
    {
        $mutableSchema = new MutableSchema();
        $mutableSchema->populate($schema);

        $this->factory->customObjectSchemas()->update(
            SchemaUtility::makeFullyQualifiedName($name),
            $mutableSchema->toArray()
        );
    }

    /**
     * Delete a schema.
     *
     * @param string $name
     */
    public function delete(string $name)
    {
        $this->registry->remove('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $name);

        $labels = $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA, []);
        unset($labels[$name]);
        $this->registry->set('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA, $labels);

        $this->factory->customObjectSchemas()->delete(SchemaUtility::makeFullyQualifiedName($name));
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

    /**
     * @param $schema
     * @param array $schemaLabels
     * @return array
     */
    protected function persistSchemaInRegistry($schema): void
    {
        $this->registry->set(
            'hubspot',
            self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $schema['name'],
            $schema
        );

        $this->persistSchemaLabelInRegistry($schema['name'], $schema['labels']['singular']);
    }

    /**
     * @param string $schemaName
     * @param string $schemaLabel
     * @return void
     */
    protected function persistSchemaLabelInRegistry(string $schemaName, string $schemaLabel): void
    {
        $schemaLabels = $this->getSchemaLabelsFromRegistry();

        $schemaLabels[$schemaName] = $schemaLabel;

        $this->registry->set(
            'hubspot',
            self::REGISTRY_CUSTOM_OBJECT_SCHEMA,
            $schemaLabels
        );
    }

    /**
     * Returns a specific schema from registry or null if it hasn't been set there.
     *
     * @param string $schemaName
     * @return array|null
     */
    protected function getSchemaFromRegistry(string $schemaName): ?array
    {
        return $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA . '_' . $schemaName);
    }

    /**
     * Get schema labels from registry.
     *
     * @return array|null
     */
    protected function getSchemaLabelsFromRegistry(): ?array
    {
        return $this->registry->get('hubspot', self::REGISTRY_CUSTOM_OBJECT_SCHEMA);
    }
}
