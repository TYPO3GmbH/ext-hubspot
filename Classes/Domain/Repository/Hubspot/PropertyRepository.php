<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Repository\Hubspot;

use T3G\Hubspot\Domain\Model\Hubspot\Dto\MutableProperty;
use T3G\Hubspot\Utility\SchemaUtility;

/**
 * Repository for Hubspot properties.
 */
class PropertyRepository extends AbstractHubspotRepository
{
    /**
     * Update a property.
     *
     * @param string $schemaName The name of the schema (object type)
     * @param string $propertyName The property name
     * @param array $properties The property values
     */
    public function update(string $schemaName, string $propertyName, array $property)
    {
        // Ignore internal Hubspot properties.
        if (strpos($propertyName, 'hs_') === 0) {
            return;
        }

        $mutableProperty = new MutableProperty();
        $mutableProperty->populate($property);

        $this->factory
            ->objectProperties(SchemaUtility::makeFullyQualifiedName($schemaName))
            ->update($propertyName, $mutableProperty->toArray());
    }
}
