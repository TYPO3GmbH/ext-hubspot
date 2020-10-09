<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

use T3G\Hubspot\Repository\Traits\LimitResultTrait;

/**
 * Repository for manipulating contact data via the Hubspot API
 */
class HubspotContactRepository extends AbstractHubspotRepository
{
    use LimitResultTrait;

    /**
     *
     */
    public function getContacts()
    {
        return $this->factory->contacts()->all()->toArray();
    }
}
