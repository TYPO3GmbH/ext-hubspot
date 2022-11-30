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

/**
 * Handle Hubspot Account Information
 *
 * @see https://developers.hubspot.com/docs/api/settings/account-information-api
 */
class AccountInformation extends Resource
{
    protected const ENDPOINT_PREFIX = 'https://api.hubapi.com/account-info/v3/';

    /**
     * AccountInformation constructor.
     */
    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    /**
     * Get account details for a HubSpot account.
     *
     * @return Response
     */
    public function getDetails(): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint('details'),
            []
        );
    }

    /**
     * Get the daily API usage and limits for a HubSpot account.
     *
     * @return Response
     */
    public function getApiUsageDaily(): Response
    {
        return $this->client->request(
            'get',
            $this->getEndpoint('api-usage/daily'),
            []
        );
    }

    /**
     * @param string $postfix
     * @return string
     */
    protected function getEndpoint(string $postfix = ''): string
    {
        return self::ENDPOINT_PREFIX . ($postfix !== '' ? ltrim($postfix, '/') : '');
    }
}
