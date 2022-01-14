<?php

declare(strict_types=1);

namespace T3G\Hubspot\Domain\Model\Traits;

/**
 * Populate an object based on array values.
 */
trait PopulateFromArray
{
    /**
     * Create a new object of this class using the data provided. All keys with corresponding methods
     * `'get' . ucfirst($key)` will be used to set data.
     *
     * @param array $data
     */
    public function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);

            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }
}
