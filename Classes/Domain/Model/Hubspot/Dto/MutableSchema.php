<?php

declare(strict_types=1);

namespace T3G\Hubspot\Domain\Model\Hubspot\Dto;

use T3G\Hubspot\Domain\Model\Traits\PopulateFromArray;
use T3G\Hubspot\Domain\Model\Traits\Arrayable;

/**
 * A representation of the part of a Hubspot schema containing all properties that can be modified.
 */
class MutableSchema
{
    use Arrayable;
    use PopulateFromArray;

    /**
     * @var string
     */
    protected $primaryDisplayProperty = '';

    /**
     * @var string[]
     */
    protected $requiredProperties = [];

    /**
     * @var string[]
     */
    protected $searchableProperties = [];

    /**
     * @return string
     */
    public function getPrimaryDisplayProperty(): string
    {
        return $this->primaryDisplayProperty;
    }

    /**
     * @param string $primaryDisplayProperty
     */
    public function setPrimaryDisplayProperty(string $primaryDisplayProperty)
    {
        $this->primaryDisplayProperty = $primaryDisplayProperty;
    }

    /**
     * @return string[]
     */
    public function getRequiredProperties(): array
    {
        return $this->requiredProperties;
    }

    /**
     * @param string[] $requiredProperties
     */
    public function setRequiredProperties(array $requiredProperties)
    {
        $this->requiredProperties = $requiredProperties;
    }

    /**
     * @return string[]
     */
    public function getSearchableProperties(): array
    {
        return $this->searchableProperties;
    }

    /**
     * @param string[] $searchableProperties
     */
    public function setSearchableProperties(array $searchableProperties)
    {
        $this->searchableProperties = $searchableProperties;
    }
}
