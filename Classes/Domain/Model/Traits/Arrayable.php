<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Model\Traits;

/**
 * Includes the toArray() method that helps convert an object to an array.
 */
trait Arrayable
{
    /**
     * Returns an array of the values in the object.
     *
     * @return array
     */
    public function toArray(): array
    {
        $values = [];

        $toArrayFunction = function (&$item) {
            if (is_object($item)) {
                if (!method_exists($item, 'toArray')) {
                    throw new \UnexpectedValueException(
                        'Trying to convert to object, but encountered an object without toArray().',
                        1642109788045
                    );
                }

                $item = $item->toArray();
            }
        };

        foreach (array_keys(get_object_vars($this)) as $variableName) {
            $getter = 'get' . ucfirst($variableName);

            if (method_exists($this, $getter)) {
                $value = $this->$getter();

                if (is_array($value)) {
                    array_walk_recursive(
                        $value,
                        $toArrayFunction
                    );
                } elseif (is_object($value)) {
                    $toArrayFunction($value);
                }

                $values[$variableName] = $value;
            }
        }

        return $values;
    }
}
