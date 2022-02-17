<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Model\Hubspot\Dto;

/**
 * A representation of a Hubspot property containing all properties, also those that can only be written on create.
 */
class ImmutableProperty extends MutableProperty
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var bool
     */
    protected $hasUniqueValue = false;

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

    /**
     * @return bool
     */
    public function isUniqueValue(): bool
    {
        return $this->hasUniqueValue;
    }

    /**
     * @param bool $hasUniqueValue
     */
    public function setHasUniqueValue(bool $hasUniqueValue)
    {
        $this->hasUniqueValue = $hasUniqueValue;
    }
}
