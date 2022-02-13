<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Hubspot;

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
     * @return array
     */
    public function get(int $id): array
    {
        return $this->factory->customObjects($this->objectType)->getById($id)->toArray();
    }

    // TODO: Implement similarly to HubspotContactRepository, but with the differences necessary to deal with custom
    // TODO: objects. Methods in this class will always have to use the hubspot object type string, so it has been
    // TODO: included in the constructor.
}
