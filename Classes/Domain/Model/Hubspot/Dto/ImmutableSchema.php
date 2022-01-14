<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Model\Hubspot\Dto;

/**
 * A representation of a Hubspot schema containing all properties, also those that can only be written on create.
 */
class ImmutableSchema extends MutableSchema
{
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
                    // Properties starting with "hs_" are internal and can't be used.
                    if (strpos($property['name'], 'hs_') === 0) {
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