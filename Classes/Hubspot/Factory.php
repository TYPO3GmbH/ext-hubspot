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
use T3G\Hubspot\Hubspot\Resources\CustomObjects;

/**
 * Factory class for Hubspot. Includes custom resources not included in the standard API
 */
class Factory extends HubspotFactory
{
    /**
     * @inheritDoc
     */
    public function __call($name, $args)
    {
        if ($name === 'customObjects') {
            return new CustomObjects($this->client, ...$args);
        }

        return parent::__call($name, $args);
    }
}
