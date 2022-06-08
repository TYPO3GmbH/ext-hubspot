<?php

declare(strict_types=1);

namespace T3G\Hubspot\Domain\Repository\Hubspot;

use T3G\Hubspot\Domain\Model\Hubspot\Dto\ImmutableProperty;
use T3G\Hubspot\Domain\Model\Hubspot\Dto\MutableProperty;
use T3G\Hubspot\Utility\CustomObjectUtility;
use T3G\Hubspot\Utility\SchemaUtility;

/**
 * Repository for Hubspot properties.
 */
class PropertyRepository extends AbstractHubspotRepository
{
    /**
     * Create a property.
     *
     * @param string $schemaName
     * @param array $property
     * @return void
     */
    public function create(string $schemaName, array $property)
    {
        // Ignore internal Hubspot properties.
        if (CustomObjectUtility::isHubspotInternalPropertyName($property['name'])) {
            return;
        }

        $immutableProperty = new ImmutableProperty();
        $immutableProperty->populate($property);

        $this->factory
            ->objectProperties(SchemaUtility::makeFullyQualifiedName($schemaName))
            ->create($immutableProperty->toArray());
    }

    /**
     * Update a property.
     *
     * @param string $schemaName The name of the schema (object type)
     * @param string $propertyName The property name
     * @param array $property The property values
     */
    public function update(string $schemaName, string $propertyName, array $property)
    {
        // Ignore internal Hubspot properties.
        if (CustomObjectUtility::isHubspotInternalPropertyName($propertyName)) {
            return;
        }

        $mutableProperty = new MutableProperty();
        $mutableProperty->populate($property);

        $this->factory
            ->objectProperties(SchemaUtility::makeFullyQualifiedName($schemaName))
            ->update($propertyName, $mutableProperty->toArray());
    }
}
