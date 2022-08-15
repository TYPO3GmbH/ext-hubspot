<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event\Traits;


trait CustomObjectTrait
{
    /**
     * @var array
     */
    protected $customObject;

    /**
     * @return array
     */
    public function getCustomObject(): array
    {
        return $this->customObject;
    }

    /**
     * @param array $customObject
     */
    public function setCustomObject(array $customObject)
    {
        $this->customObject = $customObject;
    }
}
