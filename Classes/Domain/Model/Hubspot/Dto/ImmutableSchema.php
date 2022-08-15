<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Model\Hubspot\Dto;

use T3G\Hubspot\Utility\CustomObjectUtility;

/**
 * A representation of a Hubspot schema containing all properties, also those that can only be written on create.
 */
class ImmutableSchema extends MutableSchema
{
    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var MutableProperty[]
     */
    protected $properties = [];

    /**
     * @var string[]
     */
    protected $associatedObjects = [];

    /**
     * @var string
     */
    protected $name = '';

    public function populate(array $data): void
    {
        if (isset($data['properties'])) {
            $properties = [];

            /** @var ImmutableProperty $property */
            foreach ($data['properties'] as $property) {
                if (is_array($property)) {
                    if (CustomObjectUtility::isHubspotInternalPropertyName($property['name'])) {
                        continue;
                    }

                    $immutableProperty = new ImmutableProperty();
                    $immutableProperty->populate($property);

                    $properties[] = $immutableProperty;
                }
            }

            $data['properties'] = $properties;
        }

        parent::populate($data);
    }

    /**
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @param array $labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return MutableProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param MutableProperty[] $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return string[]
     */
    public function getAssociatedObjects(): array
    {
        return $this->associatedObjects;
    }

    /**
     * @param string[] $associatedObjects
     */
    public function setAssociatedObjects(array $associatedObjects)
    {
        $this->associatedObjects = $associatedObjects;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
