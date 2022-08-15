<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Hubspot;

use SevenShores\Hubspot\Factory as HubspotFactory;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\Resource;
use T3G\Hubspot\Hubspot\Resources\CustomObjects;
use T3G\Hubspot\Hubspot\Resources\CustomObjectSchemas;

/**
 * Factory class for Hubspot. Includes custom resources not included in the standard API
 *
 * @method CustomObjects customObjects(string $objectType)
 * @method CustomObjectSchemas customObjectSchemas()
 */
class Factory extends HubspotFactory
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct($config = [], $client = null, $clientOptions = [], $wrapResponse = true)
    {
        if (is_null($client)) {
            $client = new Client($config, null, $clientOptions, $wrapResponse);
        }
        $this->client = $client;

        parent::__construct($config, $client, $clientOptions, $wrapResponse);
    }

    /**
     * @inheritDoc
     */
    public function __call($name, $args): Resource
    {
        switch ($name) {
            case 'customObjects':
                return new CustomObjects($this->client, $args[0]);
            case 'customObjectSchemas':
                return new CustomObjectSchemas($this->client);
        }

        return parent::__call($name, $args);
    }
}
