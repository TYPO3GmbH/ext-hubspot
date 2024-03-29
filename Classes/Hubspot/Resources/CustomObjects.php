<?php

declare(strict_types=1);


namespace T3G\Hubspot\Hubspot\Resources;

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Http\Response;
use SevenShores\Hubspot\Resources\Resource;
use T3G\Hubspot\Utility\CustomObjectUtility;
use T3G\Hubspot\Utility\SchemaUtility;

/**
 * Handle Hubspot Custom Objects
 *
 * @see https://developers.hubspot.com/docs/api/crm/crm-custom-objects
 */
class CustomObjects extends Resource
{
    protected const ENDPOINT_PREFIX = 'https://api.hubapi.com/crm/v3/objects/';

    /**
     * @var string The custom object type
     */
    protected $objectType;

    /**
     * CustomObjects constructor.
     */
    public function __construct(Client $client, string $objectType)
    {
        parent::__construct($client);

        $this->objectType = $objectType;
    }

    /**
     * List custom objects.
     *
     * @param array $properties
     * @return Response
     */
    public function list(array $properties): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint(),
            ['json' => ['properties' => $properties]]
        );
    }

    /**
     * Create a custom object.
     *
     * @param array $properties
     * @return Response
     */
    public function create(array $properties): Response
    {
        return $this->client->request(
            'post',
            $this->getEndpoint(),
            ['json' => ['properties' => $properties]]
        );
    }

    /**
     * Update a custom object.
     *
     * @param int $id
     * @param array $properties
     * @return Response
     */
    public function update(int $id, array $properties): Response
    {
        return $this->client->request(
            'patch',
            $this->getEndpoint((string)$id),
            ['json' => ['properties' => $properties]]
        );
    }

    /**
     * Get a custom object by its ID.
     *
     * @param int $id
     * @param array $parameters Parameters for the query string
     * @return Response
     */
    public function getById(int $id, array $parameters = []): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint((string)$id),
            [],
            http_build_query($parameters)
        );
    }

    /**
     * Get a custom object by a unique property value.
     *
     * @param string $propertyName
     * @param string $propertyValue
     * @param array $parameters Parameters for the query string
     * @return \Psr\Http\Message\ResponseInterface|Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \SevenShores\Hubspot\Exceptions\HubspotException
     */
    public function getByUniqueProperty(string $propertyName, string $propertyValue, array $parameters = [])
    {
        $parameters['idProperty'] = $propertyName;

        return $this->client->request(
            'get',
            $this->getEndpoint($propertyValue),
            [],
            http_build_query($parameters)
        );
    }

    /**
     * Get a list of association from a custom object to an object type.
     *
     * @param int $id
     * @param string $toObjectType
     * @param array $properties
     * @return Response
     */
    public function associations(int $id, string $toObjectType, array $properties = []): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint($id . '/associations/' . $toObjectType),
            ['json' => ['properties' => $properties]]
        );
    }

    public function associate(
        int $id,
        string $toObjectType,
        int $toObjectId,
        string $associationType,
        array $properties = []
    )
    {
        return $this->client->request(
            'put',
            $this->getEndpoint($id . '/associations/' . $toObjectType . '/' . $toObjectId . '/' . $associationType),
            ['json' => ['properties' => $properties]]
        );
    }

    /**
     * @param string $postfix
     * @return string
     */
    protected function getEndpoint(string $postfix = ''): string
    {
        return self::ENDPOINT_PREFIX . SchemaUtility::makeFullyQualifiedName($this->objectType) . ($postfix !== '' ? '/' . $postfix : '');
    }
}
