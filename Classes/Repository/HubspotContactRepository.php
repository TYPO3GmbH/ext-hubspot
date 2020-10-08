<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

/**
 * Repository for manipulating contact data via the Hubspot API
 */
class HubspotContactRepository extends AbstractHubspotRepository
{
    /**
     * @var int Max records to include in batch operations
     */
    protected $limit = 10;

    /**
     *
     */
    public function getContacts()
    {
        return $this->factory->contacts()->all()->toArray();
    }

    /**
     * Get max records to include in batch operations
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set max records to include in batch operations
     *
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }
}
