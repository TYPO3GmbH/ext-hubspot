<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Traits;

/**
 * Adds functionality related to setting a limit to fetched results
 */
trait LimitResultTrait
{
    /**
     * @var int Max records to include in batch operations
     */
    protected $limit = 10;

    /**
     * @var array PIDs to search within. Empty array means ignore PID
     */
    protected $searchPids = [];

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

    /**
     * Get PIDs to search within. Empty array means ignore PID
     *
     * @return array
     */
    public function getSearchPids(): array
    {
        return $this->searchPids;
    }

    /**
     * Set PIDs to search within. Empty array means ignore PID
     *
     * @param array $searchPids
     */
    public function setSearchPids(array $searchPids)
    {
        $this->searchPids = $searchPids;
    }

    /**
     * Check if there are search PIDs available
     *
     * @return bool True if there are PIDs available
     */
    public function hasSearchPids(): bool
    {
        return count($this->searchPids) > 0;
    }
}
