<?php

declare(strict_types=1);

namespace T3G\Hubspot\Hubspot\Resources;

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use SevenShores\Hubspot\Http\Response;
use SevenShores\Hubspot\Resources\Resource;

class CustomObjectSchemas extends Resource
{
    protected const ENDPOINT_PREFIX = 'https://api.hubapi.com/crm/v3/schemas';

    /**
     * Get all custom object schemas.
     *
     * @param array $params Array of optional parameters ['archived']
     * @return Response
     */
    public function all(array $params = []): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint(),
            [],
            build_query_string($params)
        );
    }

    /**
     * Create a custom object schema definition.
     *
     * @param array $data
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface|Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \SevenShores\Hubspot\Exceptions\HubspotException
     */
    public function create(array $data, array $params = [])
    {
        return $this->client->request(
            'post',
            $this->getEndpoint(),
            ['json' => $data],
            build_query_string($params)
        );
    }

    /**
     * Update a custom object schema definition.
     *
     * @param string $objectType
     * @param array $data
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface|Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \SevenShores\Hubspot\Exceptions\HubspotException
     */
    public function update(string $objectType, array $data, array $params = [])
    {
        return $this->client->request(
            'patch',
            $this->getEndpoint($objectType),
            ['json' => $data],
            build_query_string($params)
        );
    }

    /**
     * Delete a schema.
     *
     * @param string $objectType
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface|Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     * @throws \SevenShores\Hubspot\Exceptions\HubspotException
     */
    public function delete(string $objectType, array $params = [])
    {
        return $this->client->request(
            'delete',
            $this->getEndpoint($objectType),
            [],
            build_query_string($params)
        );
    }

    protected function getEndpoint(string $postfix = ''): string
    {
        return self::ENDPOINT_PREFIX . ($postfix !== '' ? '/' . $postfix : '');
    }
}
