<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

use T3G\Hubspot\Hubspot\Factory;

/**
 * Abstract class for Hubspot repositories
 */
abstract class AbstractHubspotRepository
{
    /**
     * Hubspot API client.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * HubspotFormRepository constructor.
     */
    public function __construct(Factory $factory = null)
    {
        $this->factory = $factory ?? Factory::create(getenv('APP_HUBSPOT_SECRET') ?: null);
    }
}
