<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Hubspot;

use GuzzleHttp\Exception\ClientException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Hubspot\Factory;
use T3G\Hubspot\Domain\Repository\Traits\LimitResultTrait;

/**
 * Repository for Hubspot custom objects
 */
class CustomObjectRepository extends AbstractHubspotRepository
{
    use LimitResultTrait;

    /**
     * @var string
     */
    protected $objectType;

    /**
     * CustomObjectRepository constructor.
     */
    public function __construct(string $objectType, Factory $factory = null)
    {
        parent::__construct($factory);

        $this->objectType = $objectType;
    }

    /**
     * Create a custom object and return the object's ID.
     *
     * @param array $properties
     * @return int
     */
    public function create(array $properties): int
    {
        $result = $this->factory->customObjects($this->objectType)->create($properties);

        return (int)$result['id'];
    }

    /**
     * Get a custom object.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            return $this->factory->customObjects($this->objectType)->getById($id)->toArray();
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }

    }

    /**
     * Get a custom object by a unique property value.
     *
     * @param string $propertyName
     * @param string $propertyValue
     * @return array|null
     */
    public function findByUniqueProperty(string $propertyName, string $propertyValue): ?array
    {
        try {
            return $this->factory->customObjects($this->objectType)
                ->getByUniqueProperty($propertyName, $propertyValue)->toArray();
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * Returns true if this custom object has records.
     *
     * Used to check if a schema can be deleted (it can't if there are objects).
     *
     * @return bool
     */
    public function hasObjects(): bool
    {
        return count($this->factory->customObjects($this->objectType)->list(['limit' => 1])->toArray()['results']) > 0;
    }

    /**
     * Get associations from a custom object to objects of a specific type.
     *
     * @param int $objectId
     * @param string $toObjectType
     * @return array
     */
    public function findAssociations(int $objectId, string $toObjectType): array
    {
        return $this->factory->customObjects($this->objectType)->associations($objectId, $toObjectType)->toArray();
    }

    /**
     * Associate an object with another object.
     *
     * @param int $objectId
     * @param string $toObjectType
     * @param int $toObjectId
     * @param string $associationType
     * @return array
     */
    public function addAssociation(int $objectId, string $toObjectType, int $toObjectId, string $associationType): array
    {
        return $this->factory->customObjects($this->objectType)
            ->associate($objectId, $toObjectType, $toObjectId, $associationType)
            ->toArray();
    }

    // TODO: Implement similarly to HubspotContactRepository, but with the differences necessary to deal with custom
    // TODO: objects. Methods in this class will always have to use the hubspot object type string, so it has been
    // TODO: included in the constructor.
}
